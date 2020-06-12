<?php
namespace Sas\Esd\Checkout\Cart\Subscriber;

use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class OrderPlacedSubscriber
{
    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $esdOrderRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $esdSerialRepository;

    public function __construct(
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $esdOrderRepository,
        EntityRepositoryInterface $esdSerialRepository
    ) {
        $this->productRepository = $productRepository;
        $this->esdOrderRepository = $esdOrderRepository;
        $this->esdSerialRepository = $esdSerialRepository;
    }

    public function __invoke(CheckoutOrderPlacedEvent $orderPlacedEvent): void
    {
        $orderLineItemCollection = $orderPlacedEvent->getOrder()->getLineItems();

        if ($orderLineItemCollection === null) {
            return;
        }

        $productIds = array_filter($orderLineItemCollection->fmap(static function (OrderLineItemEntity $orderLineItemEntity) {
            return $orderLineItemEntity->getProductId();
        }));

        /**
         * If no products in the card return
         */
        if (empty($productIds)) {
            return;
        }

        $criteria = new Criteria($productIds);
        $criteria->addAssociation('esd');

        $products = $this->productRepository->search($criteria, $orderPlacedEvent->getContext());
        $payload = [];

        /** @var OrderLineItemEntity $orderLineItem */
        foreach ($orderLineItemCollection as $orderLineItem) {
            if ($orderLineItem->getProductId() === null) {
                continue;
            }

            /** @var ProductEntity */
            $productData = $products->get($orderLineItem->getProductId());

            /**
             * If the product has no serial or no media it's not an ESD product,
             * in that case no orderLineItem will be saved.
             */
            if ($productData->hasExtension('esd') === false || $productData->getExtension('esd')->hasSerial() === false && $productData->getExtension('esd')->getMedia() === null) {
                continue;
            }

            /** @var EsdEntity $esdEntity */
            $esdEntity = $productData->getExtension('esd');

            $payload[] = [
                'esdId'           => $esdEntity->getId(),
                'orderLineItemId' => $orderLineItem->getId(),
                'serialId'        => $this->fetchSerial($esdEntity, $orderPlacedEvent->getContext()),
            ];
        }

        if (empty($payload)) {
            return;
        }

        $this->esdOrderRepository->create($payload, $orderPlacedEvent->getContext());
    }

    private function fetchSerial(EsdEntity $esdEntity, Context $context): ?string
    {
        if (!$esdEntity->hasSerial()) {
            return null;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('esdId', $esdEntity->getId()));
        $criteria->addFilter(new EqualsFilter('esdOrder.id', null));
        $criteria->setLimit(1);

        return $this->esdSerialRepository->searchIds($criteria, $context)->firstId();
    }
}
