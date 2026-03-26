<?php
declare(strict_types=1);

namespace Sas\Esd\Service;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial\EsdSerialCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial\EsdSerialEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Sas\Esd\Utils\EsdMailTemplate;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class EsdOrderService
{
    /**
     * @param EntityRepository<EsdOrderCollection>  $esdOrderRepository
     * @param EntityRepository<EsdSerialCollection> $esdSerialRepository
     */
    public function __construct(
        private readonly EntityRepository $esdOrderRepository,
        private readonly EntityRepository $esdSerialRepository,
        private readonly EsdService $esdService
    ) {
    }

    public function addNewEsdOrders(
        OrderEntity $order,
        Context $context,
        ?ProductCollection $products = null
    ): void {
        if (!$order->getLineItems() instanceof OrderLineItemCollection) {
            return;
        }

        $newEsdOrders = [];
        foreach ($order->getLineItems() as $orderLineItem) {
            if (!\is_string($orderLineItem->getProductId())) {
                continue;
            }

            if ($products instanceof ProductCollection) {
                $product = $products->get($orderLineItem->getProductId());
                if (!$product) {
                    continue;
                }

                $esd = $product->getExtension('esd');
            } else {
                if (!$orderLineItem->getProduct()) {
                    continue;
                }
                $esd = $orderLineItem->getProduct()->getExtension('esd');
            }

            if (!$esd instanceof EsdEntity) {
                continue;
            }

            if ($esd->isHasSerial() === false && $esd->getEsdMedia() === null) {
                continue;
            }

            $fetchSerials = $this->fetchSerials($esd, $context);
            $fetchSerialIds = [];
            if ($fetchSerials !== null) {
                $fetchSerialIds = $fetchSerials->getIds();
            }

            for ($q = 0; $q < $orderLineItem->getQuantity(); ++$q) {
                $serialId = null;
                if (\count($fetchSerialIds) > 0) {
                    $serialId = current($fetchSerialIds);
                    unset($fetchSerialIds[$serialId]); // @phpstan-ignore-line
                }

                $newEsdOrders[] = [
                    'id' => Uuid::randomHex(),
                    'esdId' => $esd->getId(),
                    'orderLineItemId' => $orderLineItem->getId(),
                    'serialId' => $serialId,
                ];
            }
        }

        if (!empty($newEsdOrders)) {
            $this->esdOrderRepository->create($newEsdOrders, $context);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function mailTemplateData(OrderEntity $order, Context $context): array
    {
        if (!$order->getLineItems() instanceof OrderLineItemCollection) {
            return [];
        }

        $esdOrderListIds = [];
        $esdOrderLineItems = [];

        $criteria = new Criteria();
        $criteria->addAssociation('orderLineItem');
        $criteria->addAssociation('serial');
        $criteria->addAssociation('esd.esdMedia');
        $criteria->addFilter(
            new EqualsAnyFilter('orderLineItemId', array_values($order->getLineItems()->getIds()))
        );

        $esdOrders = $this->esdOrderRepository->search($criteria, $context)->getEntities();

        $esdByLineItemIds = [];
        $esdIds = [];

        foreach ($esdOrders as $esdOrder) {
            $esd = $esdOrder->getEsd();
            if (!$esd || !$esd->getEsdMedia() instanceof EsdMediaCollection) {
                continue;
            }

            $esdOrderLineItems[$esdOrder->getOrderLineItemId()] = $esdOrder->getOrderLineItem();
            $esdByLineItemIds[$esdOrder->getOrderLineItemId()] = $esd;
            $esdIds[] = $esdOrder->getEsdId();
        }

        $templateData['esdMediaFiles'] = [];
        $esdMedias = $this->esdService->getEsdMediaByEsdIds($esdIds, $context);

        foreach ($esdOrders as $esdOrder) {
            if (empty($esdMedias[$esdOrder->getEsdId()])) {
                continue;
            }

            foreach ($esdMedias[$esdOrder->getEsdId()] as $esdMedia) {
                $templateData['esdMediaFiles'][$esdOrder->getId()][$esdMedia->getId()] = $esdMedia;
            }
        }

        foreach ($order->getLineItems() as $orderLineItem) {
            if (!\array_key_exists($orderLineItem->getId(), $esdByLineItemIds)) {
                continue;
            }

            $esd = $esdByLineItemIds[$orderLineItem->getId()];
            if ($esd->getEsdMedia() === null) {
                continue;
            }

            $esdOrder = $esdOrders->filter(function (EsdOrderEntity $esdOrderEntity) use ($orderLineItem) {
                return $esdOrderEntity->getOrderLineItemId() === $orderLineItem->getId();
            })->first();

            if (!$esdOrder instanceof EsdOrderEntity) {
                continue;
            }

            for ($q = 0; $q < $orderLineItem->getQuantity(); ++$q) {
                $esdOrderListIds[$orderLineItem->getId()][] = $esdOrder->getId();
            }
        }

        $templateData['esdOrderLineItems'] = $esdOrderLineItems;
        $templateData['esdOrderListIds'] = $esdOrderListIds;
        $templateData['isDisableZipFile'] = $this->esdService->getSystemConfig(EsdMailTemplate::TEMPLATE_DOWNLOAD_DISABLED_ZIP_SYSTEM_CONFIG_NAME);

        if (!$templateData['isDisableZipFile']) {
            /** @var OrderLineItemEntity $lineItem */
            foreach ($esdOrderLineItems as $lineItem) {
                if (!\is_string($lineItem->getProductId())) {
                    continue;
                }
                $templateData['esdFiles'][$lineItem->getProductId()] = $this->esdService->getFileSize($lineItem->getProductId(), $context);
            }
        }

        $serialOfEsdOrders = $esdOrders->filter(function (EsdOrderEntity $esdOrderEntity) {
            return $esdOrderEntity->getSerialId() !== null;
        });

        $templateData['esdSerials'] = [];
        /** @var EsdOrderEntity $serialOfEsdOrder */
        foreach ($serialOfEsdOrders as $serialOfEsdOrder) {
            if (!$serialOfEsdOrder->getSerial() instanceof EsdSerialEntity) {
                continue;
            }

            if (!$serialOfEsdOrder->getOrderLineItem() instanceof OrderLineItemEntity) {
                continue;
            }

            $templateData['esdSerials'][] = [
                'serial' => $serialOfEsdOrder->getSerial()->getSerial(),
                'productName' => $serialOfEsdOrder->getOrderLineItem()->getLabel(),
                'orderLineItemId' => $serialOfEsdOrder->getOrderLineItemId(),
                'esdOrderId' => $serialOfEsdOrder->getId(),
            ];
        }

        return $templateData;
    }

    /**
     * Fetches serials for a given ESD entity.
     *
     * @return EntitySearchResult<EsdSerialCollection>|null
     */
    public function fetchSerials(EsdEntity $esd, Context $context): ?EntitySearchResult
    {
        if (!$esd->isHasSerial()) {
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

    public function isEsdOrder(OrderEntity $order): bool
    {
        if (!$order->getLineItems() instanceof OrderLineItemCollection) {
            return false;
        }

        foreach ($order->getLineItems() as $lineItem) {
            if (!$lineItem->getProduct()) {
                continue;
            }

            $esd = $lineItem->getProduct()->getExtension('esd');
            if (!$esd instanceof EsdEntity) {
                continue;
            }

            if (!$esd->getEsdMedia() instanceof EsdMediaCollection) {
                continue;
            }

            $esdMedias = $esd->getEsdMedia()->filter(function (EsdMediaEntity $esdMedia) {
                return $esdMedia->getMediaId() !== null;
            });

            if (!empty($esdMedias->getElements())) {
                return true;
            }
        }

        return false;
    }
}
