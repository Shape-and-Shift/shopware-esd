<?php declare(strict_types=1);

namespace Sas\Esd\Service;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EsdDownloadService
{
    /**
     * @var EntityRepositoryInterface
     */
    private $esdOrderRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $esdDownloadHistoryRepository;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(
        EntityRepositoryInterface $esdOrderRepository,
        EntityRepositoryInterface $esdDownloadHistoryRepository,
        SystemConfigService $systemConfigService
    ) {
        $this->esdOrderRepository = $esdOrderRepository;
        $this->esdDownloadHistoryRepository = $esdDownloadHistoryRepository;
        $this->systemConfigService = $systemConfigService;
    }

    public function checkLimitDownload(EsdOrderEntity $esdOrder): void
    {
        $limitNumber = $this->getLimitDownloadNumber($esdOrder);
        if ($limitNumber !== null) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('esdOrderId', $esdOrder->getId()));
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
                $limitNumber = $this->systemConfigService->get('SasEsd.config.limitDownloadNumber');
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
}
