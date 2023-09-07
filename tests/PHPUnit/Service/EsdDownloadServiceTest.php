<?php declare(strict_types=1);

namespace Sas\Esd\Tests\Service;

use PHPUnit\Framework\TestCase;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMediaDownloadHistory\EsdMediaDownloadHistoryCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMediaDownloadHistory\EsdMediaDownloadHistoryEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Sas\Esd\Service\EsdDownloadService;
use Sas\Esd\Tests\Stubs\StaticEntityRepository;
use Sas\Esd\Tests\Stubs\StaticSystemConfigService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EsdDownloadServiceTest extends TestCase
{
    private StaticEntityRepository $esdMediaDownloadHistoryRepository;

    private StaticSystemConfigService $systemConfigService;

    private EsdDownloadService $esdDownloadService;

    private Context $context;

    public function setUp(): void
    {
        $this->context = Context::createDefaultContext();
        $this->systemConfigService = new StaticSystemConfigService();
    }

    public function testThrowCheckLimitDownload(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $esdEntity = new EsdEntity();
        $esdEntity->setId(Uuid::randomHex());
        $esdEntity->setHasCustomDownloadLimit(true);
        $esdEntity->setHasUnlimitedDownload(false);
        $esdEntity->setDownloadLimitNumber(1);

        $esdOrder = new EsdOrderEntity();
        $esdOrder->setId(Uuid::randomHex());
        $esdOrder->setEsd($esdEntity);
        $esdOrder->setCountDownload(5);

        $this->esdDownloadService = new EsdDownloadService(
            new StaticEntityRepository([]),
            new StaticEntityRepository([]),
            new StaticEntityRepository([]),
            new StaticSystemConfigService(),
        );

        $this->esdDownloadService->checkLimitDownload($esdOrder);
    }

    /**
     * @dataProvider getLimitDownloadNumberProvider
     */
    public function testGetLimitDownloadNumber(?int $systemConfigData, bool $isHasCustomDownloadLimit, bool $isHasUnlimitedDownload, bool $isNotDownloadLimitation): void
    {
        $esdEntity = new EsdEntity();
        $esdEntity->setId(Uuid::randomHex());
        $esdEntity->setHasUnlimitedDownload($isHasUnlimitedDownload);
        $esdEntity->setHasCustomDownloadLimit($isHasCustomDownloadLimit);
        if (\is_int($systemConfigData)) {
            $esdEntity->setDownloadLimitNumber($systemConfigData);
        }

        $esdOrder = $this->createMock(EsdOrderEntity::class);
        $esdOrder->method('getEsd')->willReturn($esdEntity);

        if (!$isHasCustomDownloadLimit && !$isHasUnlimitedDownload && !$isNotDownloadLimitation) {
            $this->systemConfigService = new StaticSystemConfigService([
                'SasEsd.config.isNotDownloadLimitation' => $isNotDownloadLimitation,
                'SasEsd.config.limitDownloadNumber' => $systemConfigData,
            ]);
        }

        $this->esdDownloadService = new EsdDownloadService(
            new StaticEntityRepository([]),
            new StaticEntityRepository([]),
            new StaticEntityRepository([]),
            $this->systemConfigService
        );

        $limitDownloadNumber = $this->esdDownloadService->getLimitDownloadNumber($esdOrder);

        static::assertSame($systemConfigData, $limitDownloadNumber);
    }

    /**
     * @return void
     *              Test getLimitDownloadNumberList return array
     */
    public function testGetLimitDownloadNumberList(): void
    {
        $esdEntity = new EsdEntity();
        $esdEntity->setId(Uuid::randomHex());
        $esdEntity->setHasCustomDownloadLimit(true);
        $esdEntity->setHasUnlimitedDownload(false);
        $esdEntity->setDownloadLimitNumber(1);

        $esdOrderEntity = new EsdOrderEntity();
        $esdOrderEntity->setId(Uuid::randomHex());
        $esdOrderEntity->setEsd($esdEntity);

        $esdOrderCollection = new EsdOrderCollection();
        $esdOrderCollection->add($esdOrderEntity);

        $this->esdDownloadService = new EsdDownloadService(
            new StaticEntityRepository([]),
            new StaticEntityRepository([]),
            new StaticEntityRepository([]),
            new StaticSystemConfigService([]),
        );

        $actualValue = $this->esdDownloadService->getLimitDownloadNumberList($esdOrderCollection);

        static::assertArrayHasKey($esdOrderEntity->getId(), $actualValue);
        static::assertSame($actualValue[$esdOrderEntity->getId()], 1);
    }

    public function testAddDownloadHistory(): void
    {
        $esdDownloadHistoryRepository = $this->createMock(EntityRepository::class);
        $esdOrderRepository = $this->createMock(EntityRepository::class);
        $esdDownloadHistoryRepository->expects(static::once())->method('create');
        $esdOrderRepository->expects(static::once())->method('update');

        $esdOrder = new EsdOrderEntity();
        $esdOrder->setId(Uuid::randomHex());
        $esdOrder->setCountDownload(1);

        $this->esdDownloadService = new EsdDownloadService(
            $esdOrderRepository,
            $esdDownloadHistoryRepository,
            new StaticEntityRepository([]),
            new StaticSystemConfigService()
        );

        $this->esdDownloadService->addDownloadHistory($esdOrder, $this->context);
    }

    public function testThrowCheckMediaDownloadHistory(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $esdOrderId = 'foo';

        $esdMedia = new EsdMediaEntity();
        $esdMedia->setId(Uuid::randomHex());
        $esdMedia->setDownloadLimitNumber(1);

        $esdOrder = new EsdOrderEntity();
        $esdOrder->setId(Uuid::randomHex());
        $esdOrder->setEsd(new EsdEntity());

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'getTotal' => 2,
        ]);

        $esdMediaDownloadHistoryRepository = $this->createMock(EntityRepository::class);
        $esdMediaDownloadHistoryRepository->expects(static::once())->method('search')->willReturn($search);

        $this->esdDownloadService = new EsdDownloadService(
            new StaticEntityRepository([]),
            new StaticEntityRepository([]),
            $esdMediaDownloadHistoryRepository,
            new StaticSystemConfigService()
        );

        $this->esdDownloadService->checkMediaDownloadHistory($esdOrderId, $esdMedia, $esdOrder, $this->context);
    }

    public function testGetDownloadRemainingItems(): void
    {
        $esdOrderId = 'foo';
        $esdMediaId = 'bar';

        $esdMediaDownloadHistoryCollection = new EsdMediaDownloadHistoryCollection();

        $esdMediaDownloadHistoryEntity1 = new EsdMediaDownloadHistoryEntity();
        $esdMediaDownloadHistoryEntity1->setId(Uuid::randomHex());
        $esdMediaDownloadHistoryEntity1->setEsdOrderId($esdOrderId);
        $esdMediaDownloadHistoryEntity1->setEsdMediaId($esdMediaId);
        $esdMediaDownloadHistoryCollection->add($esdMediaDownloadHistoryEntity1);

        $esdMediaDownloadHistoryEntity2 = new EsdMediaDownloadHistoryEntity();
        $esdMediaDownloadHistoryEntity2->setId(Uuid::randomHex());
        $esdMediaDownloadHistoryEntity2->setEsdOrderId($esdOrderId);
        $esdMediaDownloadHistoryEntity2->setEsdMediaId($esdMediaId);
        $esdMediaDownloadHistoryCollection->add($esdMediaDownloadHistoryEntity2);

        $this->esdMediaDownloadHistoryRepository = new StaticEntityRepository([
            new EsdMediaDownloadHistoryCollection([$esdMediaDownloadHistoryEntity1, $esdMediaDownloadHistoryEntity2]),
        ]);

        $this->esdDownloadService = new EsdDownloadService(
            new StaticEntityRepository([]),
            new StaticEntityRepository([]),
            $this->esdMediaDownloadHistoryRepository,
            new StaticSystemConfigService()
        );

        $actualValue = $this->esdDownloadService->getDownloadRemainingItems([$esdOrderId], $this->context);

        static::assertArrayHasKey($esdOrderId, $actualValue);
        static::assertSame($actualValue[$esdOrderId][$esdMediaId], 2);
    }

    public function testAddMediaDownloadHistory(): void
    {
        $esdMediaDownloadHistoryRepository = $this->createMock(EntityRepository::class);
        $esdMediaDownloadHistoryRepository->expects(static::once())->method('create');
        $this->esdDownloadService = new EsdDownloadService(
            new StaticEntityRepository([]),
            new StaticEntityRepository([]),
            $esdMediaDownloadHistoryRepository,
            new StaticSystemConfigService()
        );
        $this->esdDownloadService->addMediaDownloadHistory('test', 'test', $this->context);
    }

    public function getLimitDownloadNumberProvider(): array
    {
        return [
            'Test limitDownloadNumber can be set' => [
                1, true, false, false,
            ],
            'Test limitDownloadNumber can be set 2' => [
                1, false, false, false,
            ],
            'Test limitDownloadNumber can be null' => [
                null, false, false, true,
            ],
        ];
    }
}
