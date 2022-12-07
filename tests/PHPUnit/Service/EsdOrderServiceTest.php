<?php declare(strict_types=1);

namespace Sas\Esd\Tests\Service;

use PHPUnit\Framework\TestCase;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial\EsdSerialEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Sas\Esd\Service\EsdOrderService;
use Sas\Esd\Service\EsdService;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;

class EsdOrderServiceTest extends TestCase
{
    private EntityRepositoryInterface $esdOrderRepository;

    private EntityRepositoryInterface $esdSerialRepository;

    private EsdService $esdService;

    private EsdOrderService $esdOrderService;

    private Context $context;

    public function setup(): void
    {
        $this->esdOrderRepository = $this->createMock(EntityRepository::class);

        $this->esdSerialRepository = $this->createMock(EntityRepository::class);

        $this->esdService = $this->createMock(EsdService::class);

        $this->context = $this->createMock(Context::class);

        $this->esdOrderService = new EsdOrderService(
            $this->esdOrderRepository,
            $this->esdSerialRepository,
            $this->esdService
        );
    }

    /**
     * @dataProvider  addNewEsdOrdersProvider
     */
    public function testAddNewEsdOrders(?ProductCollection $products): void
    {
        $this->esdOrderRepository->expects(static::once())->method('create');

        $order = $this->getOrder();

        $this->esdOrderService->addNewEsdOrders($order, $this->context, $products);
    }

    public function testFetchSerials(): void
    {
        $esd = $this->getEsd(true);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'getTotal' => 1,
        ]);

        $this->esdSerialRepository->expects(static::once())->method('search')->willReturn($search);

        $value = $this->esdOrderService->fetchSerials($esd, $this->context);

        static::assertInstanceOf(EntitySearchResult::class, $value);
    }

    public function testFetchSerialsNullWhenHasSerialIsFalse(): void
    {
        $esd = $this->getEsd();

        $value = $this->esdOrderService->fetchSerials($esd, $this->context);

        static::assertSame($value, null);
    }

    public function testFetchSerialsNullWhenTotalEqualZero(): void
    {
        $esd = $this->getEsd(true);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'getTotal' => 0,
        ]);

        $this->esdSerialRepository->expects(static::once())->method('search')->willReturn($search);

        $value = $this->esdOrderService->fetchSerials($esd, $this->context);

        static::assertSame($value, null);
    }

    public function testMailTemplateData(): void
    {
        $order = $this->getOrder();

        $esdOrderCollection = new EsdOrderCollection();

        $esdOrder = new EsdOrderEntity();
        $esdOrder->setId('foo');

        $orderLineItem = new OrderLineItemEntity();
        $orderLineItem->setId('orderLineItem');
        $orderLineItem->setProductId('productId');
        $orderLineItem->setLabel('OrderLineItemLabel');

        $esdOrder->setOrderLineItem($orderLineItem);
        $esdOrder->setOrderLineItemId($orderLineItem->getId());

        $esd = $this->getEsd();
        $esd->setUniqueIdentifier('esdUniqueIdentifier');

        $esdMediaCollection = new EsdMediaCollection();

        $esdMedia = new EsdMediaEntity();
        $esdMedia->setId('test');
        $esdMedia->setUniqueIdentifier('esdMediaUniqueIdentifier');

        $esdMediaCollection->add($esdMedia);

        $esd->setEsdMedia($esdMediaCollection);

        $esdOrder->setEsdId('esdId');
        $esdOrder->setEsd($esd);

        $esdSerial = new EsdSerialEntity();
        $esdSerial->setId('serialId');
        $esdSerial->setSerial('serial');

        $esdOrder->setSerial($esdSerial);
        $esdOrder->setSerialId($esdSerial->getId());

        $esdOrderCollection->add($esdOrder);

        $orderSearch = $this->createConfiguredMock(EntitySearchResult::class, [
            'getEntities' => $esdOrderCollection,
            'filter' => $esdOrderCollection,
        ]);

        $this->esdOrderRepository->expects(static::once())->method('search')->willReturn($orderSearch);

        $esdMediaByEsdIds[$esd->getId()][$esdMedia->getId()] = $esdMedia;
        $this->esdService->expects(static::once())->method('getEsdMediaByEsdIds')->willReturn($esdMediaByEsdIds);
        $this->esdService->expects(static::once())->method('getFileSize')->willReturn('testFileSize');

        $templateData = $this->esdOrderService->mailTemplateData($order, $this->context);

        static::assertNotEmpty($templateData);
        static::assertArrayHasKey('esdMediaFiles', $templateData);
        static::assertArrayHasKey('esdOrderLineItems', $templateData);
        static::assertArrayHasKey('esdOrderListIds', $templateData);
        static::assertArrayHasKey('esdFiles', $templateData);
        static::assertArrayHasKey('esdSerials', $templateData);
    }

    public function testIsEsdOrder(): void
    {
        $order = $this->getOrder();

        $isEsdOrder = $this->esdOrderService->isEsdOrder($order);

        static::assertTrue($isEsdOrder);
    }

    public function testIsNotEsdOrder(): void
    {
        $orderLineItemCollection = new OrderLineItemCollection();

        $order = new OrderEntity();
        $order->setLineItems($orderLineItemCollection);

        $isEsdOrder = $this->esdOrderService->isEsdOrder($order);

        static::assertFalse($isEsdOrder);
    }

    public function getOrder(): OrderEntity
    {
        $product = new ProductEntity();
        $product->setId('productId');

        $esd = $this->getEsd(true);

        $extensions['esd'] = $esd;
        $product->setExtensions($extensions);

        $esdMedia = new EsdMediaEntity();
        $esdMedia->setId('esdMediaId');
        $esdMedia->setMediaId('mediaId');
        $esdMedia->setUniqueIdentifier('mediaUniqueIdentifier');

        $esdMediaCollection = new EsdMediaCollection();
        $esdMediaCollection->add($esdMedia);
        $esdMediaCollection->set('item', $esdMedia);

        $esd->setEsdMedia($esdMediaCollection);

        $orderLineItemEntity = new OrderLineItemEntity();
        $orderLineItemEntity->setId('orderLineItem');
        $orderLineItemEntity->setProductId($product->getId());
        $orderLineItemEntity->setProduct($product);
        $orderLineItemEntity->setQuantity(1);

        $orderLineItemCollection = new OrderLineItemCollection();
        $orderLineItemCollection->add($orderLineItemEntity);

        $order = new OrderEntity();
        $order->setLineItems($orderLineItemCollection);

        return $order;
    }

    public function addNewEsdOrdersProvider(): array
    {
        $product = new ProductEntity();
        $product->setId('productId');

        $esd = $this->getEsd(true);

        $extensions['esd'] = $esd;
        $product->setExtensions($extensions);

        $esdMediaCollection = new EsdMediaCollection();
        $esd->setEsdMedia($esdMediaCollection);

        $productCollection = new ProductCollection();
        $productCollection->add($product);

        return [
            'ProductCollection can be set' => [
                $productCollection,
            ],
            'ProductCollection can be null' => [
                null,
            ],
        ];
    }

    public function getEsd($hasSerial = false): EsdEntity
    {
        $esd = new EsdEntity();
        $esd->setId('esdId');
        $esd->setHasSerial($hasSerial);

        return $esd;
    }
}
