<?php declare(strict_types=1);

namespace Sas\Esd\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMediaDownloadHistory\EsdMediaDownloadHistoryCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMediaDownloadHistory\EsdMediaDownloadHistoryEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Sas\Esd\Service\EsdDownloadService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EsdDownloadServiceTest extends TestCase
{
    private EntityRepositoryInterface $esdOrderRepository;

    private EntityRepositoryInterface $esdDownloadHistoryRepository;

    private EntityRepositoryInterface $esdMediaDownloadHistoryRepository;

    /** @var MockObject|SystemConfigService */
    private $systemConfigService;

    private EsdDownloadService $esdDownloadService;

    private Context $context;

    public function setUp(): void
    {
        $this->context = $this->createMock(Context::class);

        $this->systemConfigService = $this->createMock(SystemConfigService::class);

        $this->esdOrderRepository = $this->createMock(EntityRepository::class);

        $this->esdDownloadHistoryRepository = $this->createMock(EntityRepository::class);

        $this->esdMediaDownloadHistoryRepository = $this->createMock(EntityRepository::class);

        $this->esdDownloadService = new EsdDownloadService(
            $this->esdOrderRepository,
            $this->esdDownloadHistoryRepository,
            $this->esdMediaDownloadHistoryRepository,
            $this->systemConfigService
        );
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

        $this->esdDownloadService->checkLimitDownload($esdOrder);
    }

    /**
     * @return void
     * @dataProvider getLimitDownloadNumberProvider
     */
    public function testGetLimitDownloadNumber(?int $systemConfigData, bool $isHasCustomDownloadLimit, bool $isHasUnlimitedDownload, bool $isNotDownloadLimitation): void
    {
        $esdEntity = $this->createMock(EsdEntity::class);
        $esdEntity->method('getHasCustomDownloadLimit')->willReturn($isHasCustomDownloadLimit);
        $esdEntity->method('getHasUnlimitedDownload')->willReturn($isHasUnlimitedDownload);
        $esdEntity->method('getDownloadLimitNumber')->willReturn($systemConfigData);

        $esdOrder = $this->createMock(EsdOrderEntity::class);
        $esdOrder->method('getEsd')->willReturn($esdEntity);

        if(!$isHasCustomDownloadLimit && !$isHasUnlimitedDownload && !$isNotDownloadLimitation) {
            $this->systemConfigService
                ->expects(static::exactly(2))
                ->method('get')
                ->willReturnOnConsecutiveCalls($isNotDownloadLimitation, $systemConfigData);
        }

        $limitDownloadNumber = $this->esdDownloadService->getLimitDownloadNumber($esdOrder);

        $this->assertSame($systemConfigData, $limitDownloadNumber);
    }

    /**
     * @return void
     * Test getLimitDownloadNumberList return array
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

        $actualValue = $this->esdDownloadService->getLimitDownloadNumberList($esdOrderCollection);

        $this->assertArrayHasKey($esdOrderEntity->getId(), $actualValue);
        $this->assertSame($actualValue[$esdOrderEntity->getId()], 1);
    }

    public function testAddDownloadHistory(): void
    {
        $this->esdDownloadHistoryRepository->expects(static::once())->method('create');
        $this->esdOrderRepository->expects(static::once())->method('update');

        $esdOrder = new EsdOrderEntity();
        $esdOrder->setId(Uuid::randomHex());
        $esdOrder->setCountDownload(1);

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
            'getTotal' => 2
        ]);

        $this->esdMediaDownloadHistoryRepository->expects(self::once())->method('search')->willReturn($search);

        $this->esdDownloadService->checkMediaDownloadHistory($esdOrderId, $esdMedia, $esdOrder, $this->context);
    }

    /**
     * @return void
     */
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

        $searchResult = new EntitySearchResult('esd_media_download_history', 1, $esdMediaDownloadHistoryCollection, null, new Criteria(), $this->context);
        $this->esdMediaDownloadHistoryRepository->expects(static::any())->method('search')->willReturn($searchResult);

        $actualValue = $this->esdDownloadService->getDownloadRemainingItems([$esdOrderId], $this->context);

        $this->assertArrayHasKey($esdOrderId, $actualValue);
        $this->assertSame($actualValue[$esdOrderId][$esdMediaId], 2);
    }

    /**
     * @return void
     */
    public function testAddMediaDownloadHistory(): void
    {
        $this->esdMediaDownloadHistoryRepository->expects(static::once())->method('create');

        $this->esdDownloadService->addMediaDownloadHistory('test','test', $this->context);
    }

    public function getLimitDownloadNumberProvider(): array
    {
        return [
            'Test limitDownloadNumber can be set' => [
                1, true, false, false
            ],
            'Test limitDownloadNumber can be set 2' => [
                1, false, false, false
            ],
            'Test limitDownloadNumber can be null' => [
                null, false, false, true
            ]
        ];
    }
}
