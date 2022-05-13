<?php declare(strict_types=1);

namespace Sas\Esd\Tests\Service;

use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\TestCase;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdVideo\EsdVideoCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdVideo\EsdVideoEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdCollection;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Sas\Esd\Service\EsdService;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class EsdServiceTest extends TestCase
{
    private EntityRepositoryInterface $esdProductRepository;

    private EntityRepositoryInterface $esdOrderRepository;

    private EntityRepositoryInterface $productRepository;

    private UrlGeneratorInterface $urlGenerator;

    private FilesystemInterface $filesystemPrivate;

    private EntityRepositoryInterface $esdVideoRepository;

    private SystemConfigService $systemConfigService;

    private EsdService $esdService;

    private Context $context;

    private SalesChannelContext $salesChannelContext;

    public function setUp(): void
    {
        $this->esdProductRepository = $this->createMock(EntityRepository::class);

        $this->esdOrderRepository = $this->createMock(EntityRepository::class);

        $this->productRepository = $this->createMock(EntityRepository::class);

        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->filesystemPrivate = $this->createMock(FilesystemInterface::class);

        $this->esdVideoRepository = $this->createMock(EntityRepository::class);

        $this->systemConfigService = $this->createMock(SystemConfigService::class);

        $this->context = $this->createMock(Context::class);

        $this->salesChannelContext = $this->createMock(SalesChannelContext::class);

        $this->esdService = new EsdService(
            $this->esdProductRepository,
            $this->esdOrderRepository,
            $this->productRepository,
            $this->urlGenerator,
            $this->filesystemPrivate,
            $this->esdVideoRepository,
            $this->systemConfigService
        );
    }

    public function testCompressFiles(): void
    {
        $product = $this->getProduct();

        $esd = $this->getEsd();

        $media = $this->getMedia();

        $esdMedia = $this->getEsdMedia($media);

        $esdMediaCollection = $this->getEsdMediaCollection($esdMedia);

        $esd->setEsdMedia($esdMediaCollection);

        $searchEsdProduct = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $esd
        ]);

        $this->esdProductRepository->expects(self::once())->method('search')->willReturn($searchEsdProduct);

        $searchProduct = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $product
        ]);

        $this->productRepository->expects(self::once())->method('search')->willReturn($searchProduct);

        $this->urlGenerator->expects(self::any())->method('getRelativeMediaUrl')->willReturn(__DIR__ . '/Image/logo.svg');

        $this->esdService->compressFiles('productId');
    }

    /**
     * @dataProvider getEsdMediaByProductIdProvider
     */
    public function testGetEsdMediaByProductId(?EsdEntity $esd): void
    {
        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $esd
        ]);

        $this->esdProductRepository->expects(self::once())->method('search')->willReturn($search);

        $esdMediaCollection = $this->esdService->getEsdMediaByProductId('test', $this->context);

        if ($esd instanceof EsdEntity && $esdMediaCollection instanceof EsdMediaCollection) {
            $this->assertInstanceOf(EsdMediaCollection::class, $esdMediaCollection);
        } else {
            $this->assertSame(null, $esdMediaCollection);
        }
    }

    public function testGetEsdMediaByEsdIds(): void
    {
        $esd = $this->getEsd();

        $media = $this->getMedia();

        $esdMedia = $this->getEsdMedia($media);

        $esdMediaCollection = $this->getEsdMediaCollection($esdMedia);

        $esd->setEsdMedia($esdMediaCollection);

        $esdCollection = $this->getEsdCollection($esd);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'getEntities' => $esdCollection
        ]);

        $this->esdProductRepository->expects(self::once())->method('search')->willReturn($search);

        $esdMediaByEsdIds = $this->esdService->getEsdMediaByEsdIds(['esdIds'], $this->context);

        $this->assertArrayHasKey('esdId', $esdMediaByEsdIds);
        $this->assertArrayHasKey('esdMediaId', $esdMediaByEsdIds['esdId']);
        $this->assertInstanceOf(EsdMediaEntity::class, $esdMediaByEsdIds['esdId']['esdMediaId']);
    }

    public function testGetEsdMediaByEsdIdsEmptyWhenEsdCollectionIsEmpty(): void
    {
        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'getEntities' => new EsdCollection()
        ]);

        $this->esdProductRepository->expects(self::once())->method('search')->willReturn($search);

        $esdMediaByEsdIds = $this->esdService->getEsdMediaByEsdIds(['esdIds'], $this->context);

        $this->assertSame([], $esdMediaByEsdIds);
    }

    public function testGetEsdMediaByEsdIdsEmptyWhenEsdMediaIsEmpty(): void
    {
        $esd = $this->getEsd();

        $esdMedia = $this->getEsdMedia();

        $esdMediaCollection = $this->getEsdMediaCollection($esdMedia);

        $esd->setEsdMedia($esdMediaCollection);

        $esdCollection = $this->getEsdCollection($esd);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'getEntities' => $esdCollection
        ]);

        $this->esdProductRepository->expects(self::once())->method('search')->willReturn($search);

        $esdMediaByEsdIds = $this->esdService->getEsdMediaByEsdIds(['esdIds'], $this->context);

        $this->assertSame([], $esdMediaByEsdIds);
    }

    public function testGetEsdVideo(): void
    {
        $esdVideo = new EsdVideoEntity();
        $esdVideo->setId('esdVideoId');
        $esdVideo->setEsdMediaId('esdMediaId');

        $esdVideoCollection = new EsdVideoCollection();
        $esdVideoCollection->add($esdVideo);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'getEntities' => $esdVideoCollection
        ]);

        $this->esdVideoRepository->expects(self::once())->method('search')->willReturn($search);

        $esdVideoByEsdIds = $this->esdService->getEsdVideo(['esdMediaId'], $this->context);

        $this->assertArrayHasKey('esdMediaId', $esdVideoByEsdIds);
        $this->assertInstanceOf(EsdVideoEntity::class, $esdVideoByEsdIds['esdMediaId']);
    }

    public function testGetEsdVideoEmptyWhenEsdVideoCollectionIsEmpty(): void
    {
        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'getEntities' => new EsdVideoCollection()
        ]);

        $this->esdVideoRepository->expects(self::once())->method('search')->willReturn($search);

        $esdVideoByEsdIds = $this->esdService->getEsdVideo(['esdMediaId'], $this->context);

        $this->assertSame([], $esdVideoByEsdIds);
    }

    public function testGetVideoMedia(): void
    {
        $esd = $this->getEsd();

        $media = $this->getMedia();

        $esdMedia = $this->getEsdMedia($media);
        $esdMedia->setMediaId($media->getId());

        $esdMediaCollection = $this->getEsdMediaCollection($esdMedia);

        $esd->setEsdMedia($esdMediaCollection);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $esd
        ]);

        $this->esdProductRepository->expects(self::any())->method('search')->willReturn($search);

        $actualMedia = $this->esdService->getVideoMedia('esdId', 'mediaId', $this->context);

        $this->assertInstanceOf(MediaEntity::class, $actualMedia);
    }

    public function testGetVideoMediaNull(): void
    {
        $media = $this->esdService->getVideoMedia('esdId', 'mediaId', $this->context);

        $this->assertSame(null, $media);
    }

    public function testGetMediaByLineItemId(): void
    {
        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $this->getEsdOrder()
        ]);

        $this->esdOrderRepository->expects(self::once())->method('search')->willReturn($search);

        $esdOrder = $this->esdService->getMediaByLineItemId('esdOrderId', $this->context);

        $this->assertInstanceOf(EsdOrderEntity::class, $esdOrder);
    }

    public function testGetMediaByLineItemIdNull(): void
    {
        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => null
        ]);

        $this->esdOrderRepository->expects(self::once())->method('search')->willReturn($search);

        $esdOrder = $this->esdService->getMediaByLineItemId('esdOrderId', $this->context);

        $this->assertSame(null, $esdOrder);
    }

    public function testGetMedia(): void
    {
        $esd = $this->getEsd();

        $esdMedia = $this->getEsdMedia();
        $esdMedia->setMediaId('mediaId');

        $esdMediaCollection = $this->getEsdMediaCollection($esdMedia);

        $esd->setEsdMedia($esdMediaCollection);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $esd
        ]);

        $this->esdProductRepository->expects(self::once())->method('search')->willReturn($search);

        $actualEsdMedia = $this->esdService->getMedia('esdId', 'mediaId', $this->context);

        $this->assertInstanceOf(EsdMediaEntity::class, $actualEsdMedia);
    }

    public function testGetMediaNullWhenEsdIsNull(): void
    {
        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => null
        ]);

        $this->esdProductRepository->expects(self::once())->method('search')->willReturn($search);

        $actualEsdMedia = $this->esdService->getMedia('esdId', 'mediaId', $this->context);

        $this->assertSame(null, $actualEsdMedia);
    }

    public function testGetMediaNullWhenEsdMediaIsNull(): void
    {
        $esd = $this->getEsd();

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $esd
        ]);

        $this->esdProductRepository->expects(self::once())->method('search')->willReturn($search);

        $actualEsdMedia = $this->esdService->getMedia('esdId', 'mediaId', $this->context);

        $this->assertSame(null, $actualEsdMedia);
    }

    public function testGetMediaNullWhenEsdMediaGetFirstIsNull(): void
    {
        $esd = $this->getEsd();

        $esdMedia = $this->getEsdMedia();

        $esdMediaCollection = $this->getEsdMediaCollection($esdMedia);

        $esd->setEsdMedia($esdMediaCollection);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $esd
        ]);

        $this->esdProductRepository->expects(self::once())->method('search')->willReturn($search);

        $actualEsdMedia = $this->esdService->getMedia('esdId', 'mediaId', $this->context);

        $this->assertSame(null, $actualEsdMedia);
    }

    public function testGetPathVideoMedia(): void
    {
        $this->urlGenerator->expects(self::once())->method('getRelativeMediaUrl')->willReturn('/test/image.png');

        $media = $this->getMedia();

        $pathVideoMedia = $this->esdService->getPathVideoMedia($media);

        $this->assertIsString($pathVideoMedia);
        $this->assertSame('/test/image.png', $pathVideoMedia);
    }

    public function testGetEsdOrderByCustomer(): void
    {
        $customer = new CustomerEntity();
        $customer->setId('customerId');

        $this->salesChannelContext->expects(self::once())->method('getCustomer')->willReturn($customer);

        $esdOrder = $this->getEsdOrder();

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $esdOrder
        ]);

        $this->esdOrderRepository->expects(self::once())->method('search')->willReturn($search);

        $esdOrder = $this->esdService->getEsdOrderByCustomer('orderId', $this->salesChannelContext);

        $this->assertInstanceOf(EsdOrderEntity::class, $esdOrder);
    }

    public function testGetEsdOrderByGuest(): void
    {
        $esdOrderEntity = $this->getEsdOrder();

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $esdOrderEntity
        ]);

        $this->esdOrderRepository->expects(self::once())->method('search')->willReturn($search);

        $esdOrder = $this->esdService->getEsdOrderByGuest('esdOrderId', $this->salesChannelContext);

        $this->assertInstanceOf(EsdOrderEntity::class, $esdOrder);
    }

    public function testGetEsdOrderListByCustomer(): void
    {
        $customer = new CustomerEntity();
        $customer->setId('customerId');

        $this->salesChannelContext->expects(self::once())->method('getCustomer')->willReturn($customer);

        $esdorder = $this->getEsdOrder();

        $esdorders = new EsdOrderCollection();
        $esdorders->add($esdorder);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $esdorders
        ]);

        $this->esdOrderRepository->expects(self::once())->method('search')->willReturn($search);

        $esdOrders = $this->esdService->getEsdOrderListByCustomer($this->salesChannelContext);

        $this->assertInstanceOf(EntitySearchResult::class, $esdOrders);
    }

    public function testGetEsdOrderByOrderLineItemIds(): void
    {
        $esdOrder = $this->getEsdOrder();
        $esOrders = $this->getEsdOrderCollection($esdOrder);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'getEntities' => $esOrders
        ]);

        $this->esdOrderRepository->expects(self::once())->method('search')->willReturn($search);

        $esOrders = $this->esdService->getEsdOrderByOrderLineItemIds(['testId'], $this->context);

        $this->assertInstanceOf(EsdOrderCollection::class, $esOrders);
    }

    public function testGetCompressFile(): void
    {
        $stringFile = $this->esdService->getCompressFile('productId');

        $this->assertIsString($stringFile);
        $this->assertStringContainsString('productId.zip', $stringFile);
    }

    public function testGetPathCompressFile(): void
    {
        $stringFile = $this->esdService->getPathCompressFile('productId');

        $this->assertIsString($stringFile);
        $this->assertStringContainsString('productId.zip', $stringFile);
    }

    public function testDownloadFileName(): void
    {
        $stringFile = $this->esdService->downloadFileName('test');

        $this->assertIsString($stringFile);
        $this->assertStringContainsString('test.zip', $stringFile);
    }

    public function testGetPrivateFolder(): void
    {
        $path = $this->esdService->getPrivateFolder();

        $this->assertIsString($path);
        $this->assertStringContainsString('/files/', $path);
    }

    public function testGetFileSize(): void
    {
        $fileSize = $this->esdService->getFileSize('productId');

        $this->assertIsString($fileSize);
    }

    /**
     * @dataProvider getSystemConfigProvider
     */
    public function testGetSystemConfig(string $name, $actual): void
    {
        $this->systemConfigService->expects(self::once())->method('get')->willReturn($name);

        $value = $this->esdService->getSystemConfig($name);

        $this->assertSame($value, $actual);
    }

    public function getProduct(): ProductEntity
    {
        $product = new ProductEntity();
        $product->setId('productId');
        $product->setName('productName');

        return $product;
    }

    public function getEsd(): EsdEntity
    {
        $esd = new EsdEntity();
        $esd->setId('esdId');
        $esd->setUniqueIdentifier('esdIdUniqueIdentifier');

        return $esd;
    }

    public function getEsdCollection(EsdEntity $esd): EsdCollection
    {
        $esdCollection = new EsdCollection();
        $esdCollection->add($esd);

        return $esdCollection;
    }

    public function getEsdMediaCollection(EsdMediaEntity $esdMedia): EsdMediaCollection
    {
        $esdMediaCollection = new EsdMediaCollection();
        $esdMediaCollection->add($esdMedia);

        return $esdMediaCollection;
    }

    public function getMedia(): MediaEntity
    {
        $media = new MediaEntity();
        $media->setId('mediaId');
        $media->setFileName('logo');
        $media->setFileExtension('svg');

        return $media;
    }

    public function getEsdMedia($media = null): EsdMediaEntity
    {
        $esdMedia = new EsdMediaEntity();
        $esdMedia->setId('esdMediaId');
        $esdMedia->setUniqueIdentifier('esdMediaUniqueIdentifier');
        $esdMedia->setMedia($media);

        return $esdMedia;
    }

    public function getEsdOrder(): EsdOrderEntity
    {
        $esdOrder = new EsdOrderEntity();
        $esdOrder->setId('esdOrderId');

        return $esdOrder;
    }

    public function getEsdOrderCollection(EsdOrderEntity $esdOrder): EsdOrderCollection
    {
        $esdOrderCollection = new EsdOrderCollection();
        $esdOrderCollection->add($esdOrder);

        return $esdOrderCollection;
    }

    public function getSystemConfigProvider(): array
    {
        return [
            'get SasEsd.config can be set' => [
                'test', true
            ],
            'get SasEsd.config can be empty' => [
                '', false
            ]
        ];
    }

    public function getEsdMediaByProductIdProvider(): array
    {
        $esd = $this->getEsd();
        $esdMedia = $this->getEsdMedia();
        $esdMediaCollection = $this->getEsdMediaCollection($esdMedia);
        $esd->setEsdMedia($esdMediaCollection);

        return [
            'GetEsdMediaByProductId can be return EsdMediaCollection' => [
                $esd
            ],
            'GetEsdMediaByProductId can be return null When Esd Empty' => [
                null
            ],
            'GetEsdMediaByProductId can be return null When EsdMedia Empty' => [
                $this->getEsd()
            ]
        ];
    }
}
