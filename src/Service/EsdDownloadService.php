<?php
declare(strict_types=1);

namespace Sas\Esd\Service;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdDownloadHistory\EsdDownloadHistoryCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMediaDownloadHistory\EsdMediaDownloadHistoryCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EsdDownloadService
{
    /**
     * @param EntityRepository<EsdOrderCollection>                $esdOrderRepository
     * @param EntityRepository<EsdDownloadHistoryCollection>      $esdDownloadHistoryRepository
     * @param EntityRepository<EsdMediaDownloadHistoryCollection> $esdMediaDownloadHistoryRepository
     */
    public function __construct(
        private readonly EntityRepository $esdOrderRepository,
        private readonly EntityRepository $esdDownloadHistoryRepository,
        private readonly EntityRepository $esdMediaDownloadHistoryRepository,
        private readonly SystemConfigService $systemConfigService
    ) {
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
        $esd = $esdOrder->getEsd();
        if (!$esd) {
            return null;
        }

        if ($esd->getHasCustomDownloadLimit()) {
            if ($esd->getHasUnlimitedDownload()) {
                $isUnlimited = true;
            } else {
                $limitNumber = $esd->getDownloadLimitNumber();
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

    /**
     * @return array<string, ?int>
     */
    public function getLimitDownloadNumberList(EsdOrderCollection $esdOrders): array
    {
        $limitDownloadNumberList = [];
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
        $esd = $esdOrder->getEsd();
        if (!$esd) {
            return;
        }

        if ($esd->getHasUnlimitedDownload()) {
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

    /**
     * @param array<string> $esdOrderIds
     *
     * @return array<string, array<string, int>>
     */
    public function getDownloadRemainingItems(array $esdOrderIds, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('esdOrderId', $esdOrderIds));
        $downloadHistories = $this->esdMediaDownloadHistoryRepository->search($criteria, $context);

        $mediaDownloadTotals = [];
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
