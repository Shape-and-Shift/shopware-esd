<?php declare(strict_types=1);

namespace Sas\Esd\Tests\Service;

use PHPUnit\Framework\MockObject\Rule\InvokedCount as InvokedCountMatcher;
use PHPUnit\Framework\TestCase;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial\EsdSerialEntity;
use Sas\Esd\Service\EsdMailService;
use Sas\Esd\Service\EsdOrderService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EsdMailServiceTest extends TestCase
{
    public function setUp(): void
    {
        $this->context = $this->createMock(Context::class);

        $this->systemConfigService = $this->createMock(SystemConfigService::class);
        $this->esdOrderRepository = $this->createMock(EntityRepository::class);
        $this->esdOrderService = $this->createMock(EsdOrderService::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->esdMailService = new EsdMailService(
            $this->esdOrderRepository,
            $this->esdOrderService,
            $this->systemConfigService,
            $this->eventDispatcher,
        );
    }

    /**
     * @dataProvider sendMailDownloadProvider()
     */
    public function testSendMailDownload(bool $hasOrder, bool $hasEsdOrderLineItems, bool $isDisableZipFile, bool $isSendDownloadConfirmation): void
    {
        $order = null;
        $esdOrderServiceExpect = static::never();

        if ($hasOrder) {
            $order = new OrderEntity();
            $esdOrderServiceExpect = static::once();
        }

        $this->mockData($order, $esdOrderServiceExpect, false, $hasEsdOrderLineItems);
        $eventExpect = static::once();

        if (empty($order) || !$hasEsdOrderLineItems || (!$isDisableZipFile && !$isSendDownloadConfirmation)) {
            $eventExpect = static::never();
        }

        if (!$hasOrder) {
            $this->systemConfigService
                ->expects(static::never())
                ->method('get');
        } elseif (!$hasEsdOrderLineItems) {
            $this->systemConfigService
                ->expects(static::exactly(2))
                ->method('get')
                ->willReturnOnConsecutiveCalls($isDisableZipFile, $isSendDownloadConfirmation);
        } elseif ($isDisableZipFile) {
            $this->systemConfigService
                ->expects(static::exactly(1))
                ->method('get')
                ->willReturn(true);
        } else {
            $this->systemConfigService
                ->expects(static::exactly(2))
                ->method('get')
                ->willReturnOnConsecutiveCalls(false, $isSendDownloadConfirmation);
        }

        $this->eventDispatcher->expects($eventExpect)->method('dispatch');
        $this->esdMailService->sendMailDownload('id', $this->context);
    }

    /**
     * @dataProvider sendMailSerialProvider()
     */
    public function testSendMailSerial(bool $hasOrder, bool $hasEsdSerials, bool $isSendSerialConfirmation): void
    {
        if ($hasOrder) {
            $order = new OrderEntity();
            $esdOrderServiceExpect = static::once();
            $this->systemConfigService
                ->expects(static::once())
                ->method('get')
                ->willReturnOnConsecutiveCalls($isSendSerialConfirmation);
        } else {
            $order = null;
            $esdOrderServiceExpect = static::never();
            $this->systemConfigService
                ->expects(static::never())
                ->method('get');
        }

        $this->mockData($order, $esdOrderServiceExpect, $hasEsdSerials);

        $eventExpect = static::once();
        if (empty($order) || !$hasEsdSerials || (!$isSendSerialConfirmation)) {
            $eventExpect = static::never();
        }

        $this->eventDispatcher->expects($eventExpect)->method('dispatch');
        $this->esdMailService->sendMailSerial('id', $this->context);
    }

    /**
     * @dataProvider enableMailButtonsProvider()
     */
    public function testEnableMailButtons(bool $hasOrder, array $esdOrderService, array $config, array $buttonsExpect): void
    {
        if ($hasOrder) {
            $order = new OrderEntity();
            $esdOrderServiceExpect = static::once();

            if (!$esdOrderService['isEsdOrder']) {
                $esdOrderServiceExpect = static::never();
                $this->systemConfigService
                    ->expects(static::never())
                    ->method('get');
            } else {
                $this->systemConfigService
                    ->expects(static::exactly(3))
                    ->method('get')
                    ->willReturnOnConsecutiveCalls($config['isSendDownloadConfirmation'], $config['isDisableZipFile'], $config['isSendSerialConfirmation']);
            }
            $this->esdOrderService->expects(static::once())->method('isEsdOrder')->willReturn($esdOrderService['isEsdOrder']);
        } else {
            $order = null;
            $esdOrderServiceExpect = static::never();
            $this->systemConfigService
                ->expects(static::never())
                ->method('get');
            $this->esdOrderService->expects(static::never())->method('isEsdOrder');
        }

        $this->mockData($order, $esdOrderServiceExpect, $esdOrderService['hasEsdSerials'], $esdOrderService['hasEsdOrderLineItems']);
        $buttons = $this->esdMailService->enableMailButtons('id', $this->context);
        static::assertSame($buttonsExpect, $buttons);
    }

    public function sendMailDownloadProvider(): array
    {
        return [
            'test not send when order is null' => [false, false, false, false],
            'test not send when order line items is empty' => [true, false, false, false],
            'test not send when both is enable zip file and is not send confirmation' => [true, true, false, false],
            'test send when is disable zip file' => [true, true, true, false],
            'test send when is send download confirmation' => [true, true, false, true],
        ];
    }

    public function sendMailSerialProvider(): array
    {
        return [
            'test not send when order is null' => [false, false, false],
            'test not send when serials is empty' => [true, false, false],
            'test send when is send serial confirmation' => [true, true, true],
        ];
    }

    public function enableMailButtonsProvider(): array
    {
        return [
            'test when order is null' => [
                false,
                [
                    'isEsdOrder' => false,
                    'hasEsdSerials' => false,
                    'hasEsdOrderLineItems' => false,
                ],
                [
                    'isSendDownloadConfirmation' => true,
                    'isDisableZipFile' => true,
                    'isSendSerialConfirmation' => true,
                ],
                [
                    'download' => false,
                    'serial' => false,
                ],
            ],
            'test when is not esd order' => [
                true,
                [
                    'isEsdOrder' => false,
                    'hasEsdSerials' => false,
                    'hasEsdOrderLineItems' => false,
                ],
                [
                    'isSendDownloadConfirmation' => true,
                    'isDisableZipFile' => true,
                    'isSendSerialConfirmation' => true,
                ],
                [
                    'download' => false,
                    'serial' => false,
                ],
            ],
            'test when serials is empty' => [
                true,
                [
                    'isEsdOrder' => true,
                    'hasEsdSerials' => false,
                    'hasEsdOrderLineItems' => true,
                ],
                [
                    'isSendDownloadConfirmation' => true,
                    'isDisableZipFile' => true,
                    'isSendSerialConfirmation' => true,
                ],
                [
                    'download' => true,
                    'serial' => false,
                ],
            ],
            'test when order line items is empty' => [
                true,
                [
                    'isEsdOrder' => true,
                    'hasEsdSerials' => true,
                    'hasEsdOrderLineItems' => false,
                ],
                [
                    'isSendDownloadConfirmation' => true,
                    'isDisableZipFile' => false,
                    'isSendSerialConfirmation' => true,
                ],
                [
                    'download' => false,
                    'serial' => true,
                ],
            ],
            'test when has both order line items and serial' => [
                true,
                [
                    'isEsdOrder' => true,
                    'hasEsdSerials' => true,
                    'hasEsdOrderLineItems' => true,
                ],
                [
                    'isSendDownloadConfirmation' => true,
                    'isDisableZipFile' => false,
                    'isSendSerialConfirmation' => true,
                ],
                [
                    'download' => true,
                    'serial' => true,
                ],
            ],
        ];
    }

    private function mockData(?OrderEntity $order, InvokedCountMatcher $esdOrderServiceExpect, bool $hasEsdSerials = false, bool $hasEsdOrderLineItems = false): void
    {
        $search = $this->createConfiguredMock(EntitySearchResult::class, [
            'get' => $order,
        ]);

        $this->esdOrderRepository->expects(static::once())->method('search')->willReturn($search);

        $esdSerials = $hasEsdSerials ? [new EsdSerialEntity()] : [];
        $esdOrderLineItems = $hasEsdOrderLineItems ? [new EsdOrderEntity()] : [];

        $mailTemplateData = [
            'esdSerials' => $esdSerials,
            'esdOrderLineItems' => $esdOrderLineItems,
        ];

        $this->esdOrderService->expects($esdOrderServiceExpect)->method('mailTemplateData')->willReturn($mailTemplateData);
    }
}
