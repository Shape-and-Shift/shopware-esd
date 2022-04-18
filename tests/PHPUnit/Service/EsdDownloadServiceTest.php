<?php declare(strict_types=1);

namespace Sas\Esd\Tests\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdDownloadHistory\EsdDownloadHistoryDefinition;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMediaDownloadHistory\EsdMediaDownloadHistoryDefinition;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderDefinition;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Sas\Esd\Service\EsdDownloadService;
use Sas\Esd\Tests\Fakes\FakeEntityRepository;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
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

        $esdOrder = new EsdOrderEntity();
        $esdOrder->setId(Uuid::randomHex());
        $esdOrder->setEsd($esdEntity);

        $this->esdDownloadService->checkLimitDownload($esdOrder);
    }

    /**
     * @return void
     * Test GetLimitDownloadNumber return Int
     */
    public function testIntGetLimitDownloadNumber(): void
    {
        $esdEntity = new EsdEntity();
        $esdEntity->setHasCustomDownloadLimit(true);
        $esdEntity->setDownloadLimitNumber(1);

        $esdOrder = new EsdOrderEntity();
        $esdOrder->setEsd($esdEntity);

        $actualValue = $this->esdDownloadService->getLimitDownloadNumber($esdOrder);

        $this->assertIsInt($actualValue);
    }

    /**
     * @return void
     * Test GetLimitDownloadNumber return Null
     */
    public function testNullGetLimitDownloadNumber(): void
    {
        $esdOrder = new EsdOrderEntity();
        $esdOrder->setEsd(new EsdEntity());

        $actualValue = $this->esdDownloadService->getLimitDownloadNumber($esdOrder);

        $this->assertNull($actualValue);
    }

    /**
     * @return void
     * Test getLimitDownloadNumberList return array
     */
    public function testGetLimitDownloadNumberList(): void
    {
        $esdOrderCollection = new EsdOrderCollection();

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

        $esdOrder = new EsdOrderEntity();
        $esdOrder->setEsd(new EsdEntity());

        $this->esdDownloadService->checkMediaDownloadHistory($esdOrderId, $esdMedia, $esdOrder, $this->context);
    }
}
