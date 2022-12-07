<?php declare(strict_types=1);

namespace Sas\Esd\Service;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial\EsdSerialEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Sas\Esd\Utils\EsdMailTemplate;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class EsdOrderService
{
    private EntityRepositoryInterface $esdOrderRepository;

    private EntityRepositoryInterface $esdSerialRepository;

    private EsdService $esdService;

    public function __construct(
        EntityRepositoryInterface $esdOrderRepository,
        EntityRepositoryInterface $esdSerialRepository,
        EsdService $esdService
    ) {
        $this->esdOrderRepository = $esdOrderRepository;
        $this->esdSerialRepository = $esdSerialRepository;
        $this->esdService = $esdService;
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

            if ($esd->hasSerial() === false && $esd->getEsdMedia() === null) {
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

        /** @var EsdOrderCollection $esdOrders */
        $esdOrders = $this->esdOrderRepository->search($criteria, $context)->getEntities();

        $esdByLineItemIds = [];
        $esdIds = [];

        /** @var EsdOrderEntity $esdOrder */
        foreach ($esdOrders as $esdOrder) {
            $esd = $esdOrder->getEsd();
            if ($esd->getEsdMedia() === null) {
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

            /** @var EsdMediaEntity $esdMedia */
            foreach ($esdMedias[$esdOrder->getEsdId()] as $esdMedia) {
                $templateData['esdMediaFiles'][$esdOrder->getId()][$esdMedia->getId()] = $esdMedia;
            }
        }

        /** @var OrderLineItemEntity $orderLineItem */
        foreach ($order->getLineItems() as $orderLineItem) {
            if (\array_key_exists($orderLineItem->getId(), $esdByLineItemIds)) {
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

        if (!$this->esdService->getSystemConfig(EsdMailTemplate::TEMPLATE_DOWNLOAD_DISABLED_ZIP_SYSTEM_CONFIG_NAME)) {
            /** @var OrderLineItemEntity $lineItem */
            foreach ($esdOrderLineItems as $lineItem) {
                if (!\is_string($lineItem->getProductId())) {
                    continue;
                }
                $templateData['esdFiles'][$lineItem->getProductId()] = $this->esdService->getFileSize($lineItem->getProductId());
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

            $templateData['esdSerials'][] = [
                'serial' => $serialOfEsdOrder->getSerial()->getSerial(),
                'productName' => $serialOfEsdOrder->getOrderLineItem()->getLabel(),
            ];
        }

        return $templateData;
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

    public function isEsdOrder(OrderEntity $order): bool
    {
        if (!$order->getLineItems() instanceof OrderLineItemCollection) {
            return false;
        }

        foreach ($order->getLineItems() as $lineItem) {
            if (!$lineItem->getProduct()) {
                continue;
            }

            /** @var EsdEntity $esd */
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
