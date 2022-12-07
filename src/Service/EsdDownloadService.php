<?php declare(strict_types=1);

namespace Sas\Esd\Service;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMediaDownloadHistory\EsdMediaDownloadHistoryEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EsdDownloadService
{
    private EntityRepositoryInterface $esdOrderRepository;

    private EntityRepositoryInterface $esdDownloadHistoryRepository;

    private EntityRepositoryInterface $esdMediaDownloadHistoryRepository;

    private SystemConfigService $systemConfigService;

    public function __construct(
        EntityRepositoryInterface $esdOrderRepository,
        EntityRepositoryInterface $esdDownloadHistoryRepository,
        EntityRepositoryInterface $esdMediaDownloadHistoryRepository,
        SystemConfigService $systemConfigService
    ) {
        $this->esdOrderRepository = $esdOrderRepository;
        $this->esdDownloadHistoryRepository = $esdDownloadHistoryRepository;
        $this->esdMediaDownloadHistoryRepository = $esdMediaDownloadHistoryRepository;
        $this->systemConfigService = $systemConfigService;
    }

    public function checkLimitDownload(EsdOrderEntity $esdOrder): void
    {
        $limitNumber = $this->getLimitDownloadNumber($esdOrder);
        if ($limitNumber !== null) {
            if ($esdOrder->getCountDownload() >= $limitNumber) {
                throw new NotFoundHttpException('You have limited downloads');
            }
        }
    }

    public function getLimitDownloadNumber(EsdOrderEntity $esdOrder): ?int
    {
        $limitNumber = null;
        $isCheckCustom = false;
        $isUnlimited = false;
        if ($esdOrder->getEsd()->getHasCustomDownloadLimit()) {
            if ($esdOrder->getEsd()->getHasUnlimitedDownload()) {
                $isUnlimited = true;
            } else {
                $limitNumber = $esdOrder->getEsd()->getDownloadLimitNumber();
            }

            $isCheckCustom = true;
        }

        if (!$isCheckCustom && !$isUnlimited) {
            $isNotDownloadLimitation = $this->systemConfigService->get('SasEsd.config.isNotDownloadLimitation');
            if ($isNotDownloadLimitation === false) {
                $limitNumber = (int) $this->systemConfigService->get('SasEsd.config.limitDownloadNumber');
            }
        }

        return $limitNumber;
    }

    public function getLimitDownloadNumberList(EsdOrderCollection $esdOrders): array
    {
        $limitDownloadNumberList = [];
        /** @var EsdOrderEntity $esdOrder */
        foreach ($esdOrders as $esdOrder) {
            $limitDownloadNumberList[$esdOrder->getId()] = $this->getLimitDownloadNumber($esdOrder);
        }

        return $limitDownloadNumberList;
    }

    public function addDownloadHistory(EsdOrderEntity $esdOrder, Context $context): void
    {
        // Save to download history to the future we can tracking it
        $this->esdDownloadHistoryRepository->create([
            [
                'id' => Uuid::randomHex(),
                'esdOrderId' => $esdOrder->getId(),
            ],
        ], $context);

        $this->esdOrderRepository->update([
            [
                'id' => $esdOrder->getId(),
                'countDownload' => $esdOrder->getCountDownload() + 1,
            ],
        ], $context);
    }

    public function checkMediaDownloadHistory(
        string $esdOrderId,
        EsdMediaEntity $esdMedia,
        EsdOrderEntity $esdOrder,
        Context $context
    ): void {
        if ($esdOrder->getEsd()->getHasUnlimitedDownload()) {
            return;
        }

        if (empty($esdMedia->getDownloadLimitNumber())) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('esdOrderId', $esdOrderId));
        $criteria->addFilter(new EqualsFilter('esdMediaId', $esdMedia->getId()));
        $totalDownloaded = $this->esdMediaDownloadHistoryRepository->search($criteria, $context)->getTotal();
        if ($totalDownloaded >= $esdMedia->getDownloadLimitNumber()) {
            throw new NotFoundHttpException('You have limited downloads');
        }
    }

    public function getDownloadRemainingItems(array $esdOrderIds, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('esdOrderId', $esdOrderIds));
        $downloadHistories = $this->esdMediaDownloadHistoryRepository->search($criteria, $context);

        $mediaDownloadTotals = [];
        /** @var EsdMediaDownloadHistoryEntity $downloadHistory */
        foreach ($downloadHistories as $downloadHistory) {
            if (empty($mediaDownloadTotals[$downloadHistory->getEsdOrderId()][$downloadHistory->getEsdMediaId()])) {
                $mediaDownloadTotals[$downloadHistory->getEsdOrderId()][$downloadHistory->getEsdMediaId()] = 1;

                continue;
            }

            ++$mediaDownloadTotals[$downloadHistory->getEsdOrderId()][$downloadHistory->getEsdMediaId()];
        }

        return $mediaDownloadTotals;
    }

    public function addMediaDownloadHistory(string $orderLineItemId, string $esdMediaId, Context $context): void
    {
        $this->esdMediaDownloadHistoryRepository->create([
            [
                'id' => Uuid::randomHex(),
                'esdOrderId' => $orderLineItemId,
                'esdMediaId' => $esdMediaId,
            ],
        ], $context);
    }
}
