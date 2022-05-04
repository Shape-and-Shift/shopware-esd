<?php declare(strict_types=1);

namespace Sas\Esd\Tests\Service;

use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\TestCase;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Sas\Esd\Service\EsdService;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use function PHPUnit\Framework\returnValueMap;

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

    /**
     * @dataProvider getSystemConfigProvider
     */
    public function testGetSystemConfig(string $name, $actual): void
    {
        $this->systemConfigService->expects(self::once())->method('get')->willReturn($name);

        $value = $this->esdService->getSystemConfig($name);

        $this->assertSame($value, $actual);
    }

    public function testGetEsdMediaByProductId(): void
    {
        $esd = $this->getEsd();
        $esdMedia = $this->getEsdMedia();

        $esd->setEsdMedia($esdMedia);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => $esd
        ]);

        $this->esdProductRepository->expects(self::once())->method('search')->willReturn($search);

        $esdMediaCollection = $this->esdService->getEsdMediaByProductId('test', $this->context);

        $this->assertInstanceOf(EsdMediaCollection::class, $esdMediaCollection);
    }

//    /**
//     * @dataProvider getEsdMediaByProductIdProvider
//     */
//    public function testNullGetEsdMediaByProductId() {
//        // continue
//    }

    public function getEsd(): EsdEntity
    {
        $esd = new EsdEntity();
        $esd->setId('esdId');

        return $esd;
    }

    public function getEsdMedia(): EsdMediaCollection
    {
        $esdMedia = new EsdMediaEntity();
        $esdMedia->setId('esdMediaId');
        $esdMedia->setUniqueIdentifier('esdMediaUniqueIdentifier');

        $esdMediaCollection = new EsdMediaCollection();
        $esdMediaCollection->add($esdMedia);

        return $esdMediaCollection;
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

        $esd->setEsdMedia($esdMedia);

        return [
            'Test can be return EsdMediaCollection' => [
                $esd, EsdMediaCollection::class
            ],
            'Test can be return null' => [
                null, null
            ]
        ];
    }
}
