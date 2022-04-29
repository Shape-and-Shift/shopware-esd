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
use Shopware\Core\Framework\Uuid\Uuid;

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
        $this->esdOrderRepository->expects(self::once())->method('create');

        $order = $this->getOrder();

        $this->esdOrderService->addNewEsdOrders($order, $this->context, $products);
    }

    public function testFetchSerials(): void
    {
        $esd = new EsdEntity();
        $esd->setId(Uuid::randomHex());
        $esd->setHasSerial(true);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'getTotal' => 1
        ]);

        $this->esdSerialRepository->expects(self::once())->method('search')->willReturn($search);

        $value = $this->esdOrderService->fetchSerials($esd, $this->context);

        $this->assertInstanceOf(EntitySearchResult::class, $value);
    }

    public function testNullFetchSerialsHasSerial(): void
    {
        $esd = new EsdEntity();
        $esd->setId(Uuid::randomHex());
        $esd->setHasSerial(false);

        $value = $this->esdOrderService->fetchSerials($esd, $this->context);

        $this->assertSame($value, null);
    }

    public function testNullFetchSerialsGetTotal(): void
    {
        $esd = new EsdEntity();
        $esd->setId(Uuid::randomHex());
        $esd->setHasSerial(true);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'getTotal' => 0
        ]);

        $this->esdSerialRepository->expects(self::once())->method('search')->willReturn($search);

        $value = $this->esdOrderService->fetchSerials($esd, $this->context);

        $this->assertSame($value, null);
    }

    public function testmailTemplateData(): void
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

        $esd = new EsdEntity();
        $esd->setId('bar');
        $esd->setUniqueIdentifier('esdUniqueIdentifier');

        $esdMediaCollection = new EsdMediaCollection();

        $esdMedia = new EsdMediaEntity();
        $esdMedia->setId('test');
        $esdMedia->setUniqueIdentifier('esdMediaUniqueIdentifier');

        $esdMediaCollection->add($esdMedia);

        $esd->setEsdMedia($esdMediaCollection);

        $esdOrder->setEsdId('bar');
        $esdOrder->setEsd($esd);

        $esdSerial = new EsdSerialEntity();
        $esdSerial->setId('serialId');
        $esdSerial->setSerial('serial');

        $esdOrder->setSerial($esdSerial);
        $esdOrder->setSerialId($esdSerial->getId());

        $esdOrderCollection->add($esdOrder);

        $orderSearch = $this->createConfiguredMock(EntitySearchResult::class,[
            'getEntities' => $esdOrderCollection,
            'filter' => $esdOrderCollection
        ]);

        $this->esdOrderRepository->expects(self::once())->method('search')->willReturn($orderSearch);

        $esdMediaByEsdIds[$esd->getId()][$esdMedia->getId()] = $esdMedia;
        $this->esdService->expects(self::once())->method('getEsdMediaByEsdIds')->willReturn($esdMediaByEsdIds);
        $this->esdService->expects(self::once())->method('getFileSize')->willReturn('testFileSize');

        $templateData = $this->esdOrderService->mailTemplateData($order, $this->context);

        $this->assertNotEmpty($templateData);
        $this->assertArrayHasKey('esdMediaFiles', $templateData);
        $this->assertArrayHasKey('esdOrderLineItems', $templateData);
        $this->assertArrayHasKey('esdOrderListIds', $templateData);
        $this->assertArrayHasKey('esdFiles', $templateData);
        $this->assertArrayHasKey('esdSerials', $templateData);
    }

    public function testTrueIsEsdOrder(): void
    {
        $order = $this->getOrder();

        $isEsdOrder = $this->esdOrderService->isEsdOrder($order);

        $this->assertTrue($isEsdOrder);
    }

    public function testFalseIsEsdOrder(): void
    {
        $orderLineItemCollection = new OrderLineItemCollection();

        $order = new OrderEntity();
        $order->setLineItems($orderLineItemCollection);

        $isEsdOrder = $this->esdOrderService->isEsdOrder($order);

        $this->assertFalse($isEsdOrder);
    }

    public function getOrder(): OrderEntity
    {
        $product = new ProductEntity();
        $product->setId('productId');

        $esd = new EsdEntity();
        $esd->setId('esdId');
        $esd->setHasSerial(true);

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

        $esd = new EsdEntity();
        $esd->setId('esdId');
        $esd->setHasSerial(true);

        $extensions['esd'] = $esd;
        $product->setExtensions($extensions);

        $esdMediaCollection = new EsdMediaCollection();
        $esd->setEsdMedia($esdMediaCollection);

        $productCollection = new ProductCollection();
        $productCollection->add($product);

        return [
            'ProductCollection can be set' => [
                $productCollection
            ],
            'ProductCollection can be null' => [
                null
            ]
        ];
    }
}
