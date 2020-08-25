<?php declare(strict_types=1);
namespace Sas\Esd\Service;

use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

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

    public function __construct(
        EntityRepositoryInterface $esdOrderRepository,
        EntityRepositoryInterface $esdSerialRepository
    ) {
        $this->esdOrderRepository = $esdOrderRepository;
        $this->esdSerialRepository = $esdSerialRepository;
    }

    public function addNewEsdOrders(
        OrderLineItemCollection $orderLineItems,
        Context $context,
        ?ProductCollection $products = null
    ): void {
        $newEsdOrders = [];
        /** @var OrderLineItemEntity $orderLineItem */
        foreach ($orderLineItems as $orderLineItem) {
            if ($products instanceof ProductCollection) {
                $product = $products->get($orderLineItem->getProductId());
                /** @var EsdEntity $esd */
                $esd = $product->getExtension('esd');
            } else {
                /** @var EsdEntity $esd */
                $esd = $orderLineItem->getProduct()->getExtension('esd');
            }

            if ($esd === null ||
                $esd->hasSerial() === false &&
                $esd->getEsdMedia() === null
            ) {
                continue;
            }

            $fetchSerials = $this->fetchSerials($esd, $context);
            for ($q = 1; $q <= $orderLineItem->getQuantity(); ++$q) {
                $serialId = null;
                if ($fetchSerials != null) {
                    $serialKey = $q - 1;
                    if (!empty($fetchSerials[$serialKey])) {
                        $serialId = $fetchSerials[$serialKey];
                    }
                }

                $newEsdOrders[] = [
                    'esdId'           => $esd->getId(),
                    'orderLineItemId' => $orderLineItem->getId(),
                    'serialId'        => $serialId,
                ];
            }
        }

        if (!empty($newEsdOrders)) {
            $this->esdOrderRepository->create($newEsdOrders, $context);
        }
    }

    public function fetchSerials(EsdEntity $esd, Context $context): ?array
    {
        if (!$esd->hasSerial()) {
            return null;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('esdId', $esd->getId()));
        $criteria->addFilter(new EqualsFilter('esdOrder.id', null));

        $esdSerial = $this->esdSerialRepository->searchIds($criteria, $context);
        if ($esdSerial->getTotal() == 0) {
            return null;
        }
        return $esdSerial->getIds();
    }
}
