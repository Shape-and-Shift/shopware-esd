<?php declare(strict_types=1);

namespace Sas\Esd\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdDownloadHistory\EsdDownloadHistoryDefinition;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMediaDownloadHistory\EsdMediaDownloadHistoryDefinition;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMediaDownloadHistory\EsdMediaDownloadHistoryEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderDefinition;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Sas\Esd\Service\EsdDownloadService;
use Sas\Esd\Tests\Fakes\FakeEntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class EsdDownloadServiceTest extends TestCase
{
    /** @var EntityRepositoryInterface */
    private $esdOrderRepository;

    /** @var EntityRepositoryInterface  */
    private $esdDownloadHistoryRepository;

    /** @var EntityRepositoryInterface  */
    private $esdMediaDownloadHistoryRepository;

    /** @var MockObject|SystemConfigService */
    private $systemConfigService;

    private EsdDownloadService $esdDownloadService;

    private Context $context;

    public function setUp(): void
    {
        $this->context = $this->createMock(Context::class);
        $this->systemConfigService = $this->createMock(SystemConfigService::class);
        $event = $this->createMock(EntityWrittenContainerEvent::class);

        $this->esdOrderRepository = new FakeEntityRepository(new EsdOrderDefinition());
        $this->esdOrderRepository->entityWrittenContainerEvents[] = $event;

        $this->esdDownloadHistoryRepository = new FakeEntityRepository(new EsdDownloadHistoryDefinition());
        $this->esdDownloadHistoryRepository->entityWrittenContainerEvents[] = $event;

        $this->esdMediaDownloadHistoryRepository = new FakeEntityRepository(new EsdMediaDownloadHistoryDefinition());
        $this->esdMediaDownloadHistoryRepository->entityWrittenContainerEvents[] = $event;

        $this->esdDownloadService = new EsdDownloadService(
            $this->esdOrderRepository,
            $this->esdDownloadHistoryRepository,
            $this->esdMediaDownloadHistoryRepository,
            $this->systemConfigService
        );
    }

    public function testCheckLimitDownload(): void
    {
        $esdEntity = new EsdEntity();
        $esdEntity->setId(Uuid::randomHex());
        $esdEntity->setHasCustomDownloadLimit(true);
        $esdEntity->setHasUnlimitedDownload(false);
        $esdEntity->setDownloadLimitNumber(1);

        $esdOrder = new EsdOrderEntity();
        $esdOrder->setId(Uuid::randomHex());
        $esdOrder->setEsd($esdEntity);

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

        $this->assertIsArray($actualValue);
    }

    public function testAddDownloadHistory(): void
    {
        $esdOrder = new EsdOrderEntity();
        $esdOrder->setId(Uuid::randomHex());

        $this->esdDownloadService->addDownloadHistory($esdOrder, $this->context);
    }

    public function testCheckMediaDownloadHistory(): void
    {
        $esdOrderId = 'foo';

        $esdMedia = new EsdMediaEntity();
        $esdMedia->setId(Uuid::randomHex());
        $esdMedia->setDownloadLimitNumber(2);

        $esdEntity = new EsdEntity();
        $esdEntity->setId(Uuid::randomHex());
        $esdEntity->setHasUnlimitedDownload(true);

        $esdOrder = new EsdOrderEntity();
        $esdOrder->setId(Uuid::randomHex());
        $esdOrder->setEsd(new EsdEntity());

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'getTotal' => 1
        ]);

        $this->esdMediaDownloadHistoryRepository->entitySearchResults[] = $search;

        $this->esdDownloadService->checkMediaDownloadHistory($esdOrderId, $esdMedia, $esdOrder, $this->context);
    }

    /**
     * @return void
     * @dataProvider mediaDownloadHistoryProvider
     */
    public function testGetDownloadRemainingItems(string $id, string $esdOrderId, string $esdMediaId): void
    {
        $esdMediaMock = $this->createConfiguredMock(EsdMediaDownloadHistoryEntity::class, [
            'getEsdOrderId' => $esdOrderId,
            'getEsdMediaId' => $esdMediaId
        ]);

        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'first' => reset($esdMediaMock),
            'last' => end($esdMediaMock),
        ]);

        $this->esdMediaDownloadHistoryRepository->entitySearchResults[] = $search;

        $actualValue = $this->esdDownloadService->getDownloadRemainingItems([$esdOrderId], $this->context);

        $this->assertIsArray($actualValue);
    }

    /**
     * @return void
     * @dataProvider addMediaDownloadHistoryProvider
     */
    public function testAddMediaDownloadHistory(string $orderLineItemId, string $esdMediaId): void
    {
        $this->esdDownloadService->addMediaDownloadHistory($orderLineItemId, $esdMediaId, $this->context);
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

    public function mediaDownloadHistoryProvider(): array
    {
        return [
            'Test item full fields' => [
                'foo', 'esdOrderId', 'esdMediaId'
            ],
            'Test item full fields 2' => [
                'bar', 'esdOrderId2', 'esdMediaId2'
            ]
        ];
    }

    public function addMediaDownloadHistoryProvider(): array
    {
        return [
            'Test data can be empty' => [
                '',''
            ],
            'Test data can be special characters' => [
                '1 s o8soadioj*&G@*@ *@ß∆ß ',' 12(*NSd (*(Shdn soihsd $# Ω≈ßß¬˚˜˜'
            ]
        ];
    }
}
