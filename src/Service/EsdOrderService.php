<?php declare(strict_types=1);

namespace Sas\Esd\Service;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial\EsdSerialEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class EsdOrderService
{
    /**
     * @var EntityRepositoryInterface
     */
    private $esdOrderRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $esdSerialRepository;

    /**
     * @var EsdMailService
     */
    private $esdMailService;

    public function __construct(
        EntityRepositoryInterface $esdOrderRepository,
        EntityRepositoryInterface $esdSerialRepository,
        EsdMailService $esdMailService
    ) {
        $this->esdOrderRepository = $esdOrderRepository;
        $this->esdSerialRepository = $esdSerialRepository;
        $this->esdMailService = $esdMailService;
    }

    public function addNewEsdOrders(
        OrderEntity $order,
        Context $context,
        ?ProductCollection $products = null
    ): void {
        $newEsdOrders = [];
        $esdSerials = [];
        $esdOrderListIds = [];
        $esdOrderLineItems = [];
        foreach ($order->getLineItems() as $orderLineItem) {
            if ($products instanceof ProductCollection) {
                $product = $products->get($orderLineItem->getProductId());
                /** @var EsdEntity $esd */
                $esd = $product->getExtension('esd');
            } else {
                /** @var EsdEntity $esd */
                $esd = $orderLineItem->getProduct()->getExtension('esd');
            }

            if ($esd === null
                || $esd->hasSerial() === false
                && $esd->getEsdMedia() === null
            ) {
                continue;
            }

            $fetchSerials = $this->fetchSerials($esd, $context);
            $fetchSerialIds = [];
            if ($fetchSerials !== null) {
                $fetchSerialIds = $fetchSerials->getIds();
            }

            for ($q = 0; $q < $orderLineItem->getQuantity(); ++$q) {
                $serialId = null;
                if (!empty($fetchSerialIds)) {
                    $serialId = current($fetchSerialIds);

                    /** @var EsdSerialEntity $serial */
                    $serial = $fetchSerials->get($serialId);
                    $esdSerials[] = [
                        'serial' => $serial->getSerial(),
                        'productName' => $orderLineItem->getLabel(),
                    ];

                    unset($fetchSerialIds[$serialId]);
                }

                $newEsdOrderId = Uuid::randomHex();
                $newEsdOrders[] = [
                    'id' => $newEsdOrderId,
                    'esdId' => $esd->getId(),
                    'orderLineItemId' => $orderLineItem->getId(),
                    'serialId' => $serialId,
                ];
                $esdOrderListIds[$orderLineItem->getId()][] = $newEsdOrderId;

                $esdOrderLineItems[$orderLineItem->getId()] = $orderLineItem;
            }
        }

        if (!empty($newEsdOrders)) {
            $this->esdOrderRepository->create($newEsdOrders, $context);
            $this->esdMailService->sendMailDownload($order, $esdOrderLineItems, $esdOrderListIds, $context);
            $this->esdMailService->sendMailSerial($order, $esdSerials, $context);
        }
    }

    public function fetchSerials(EsdEntity $esd, Context $context): ?EntitySearchResult
    {
        if (!$esd->hasSerial()) {
            return null;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('esdId', $esd->getId()));
        $criteria->addFilter(new EqualsFilter('esdOrder.id', null));

        $esdSerial = $this->esdSerialRepository->search($criteria, $context);
        if ($esdSerial->getTotal() === 0) {
            return null;
        }

        return $esdSerial;
    }
}
