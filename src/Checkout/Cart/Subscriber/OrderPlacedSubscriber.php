<?php
namespace Sas\Esd\Checkout\Cart\Subscriber;

use Sas\Esd\Service\EsdOrderService;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;

class OrderPlacedSubscriber
{
    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;


    /**
     * @var EsdOrderService
     */
    private $esdOrderService;

    public function __construct(
        EntityRepositoryInterface $productRepository,
        EsdOrderService $esdOrderService
    ) {
        $this->productRepository = $productRepository;
        $this->esdOrderService = $esdOrderService;
    }

    public function __invoke(CheckoutOrderPlacedEvent $event): void
    {
        $orderLineItems = $event->getOrder()->getLineItems();

        if ($orderLineItems === null || $event->getOrder()->getAmountTotal() > 0.0) {
            return;
        }

        $productIds = array_filter($orderLineItems->fmap(static function (OrderLineItemEntity $orderLineItem) {
            return $orderLineItem->getProductId();
        }));

        if (empty($productIds)) {
            return;
        }

        $criteria = new Criteria($productIds);
        $criteria->addAssociation('esd.esdMedia');
        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [new EqualsFilter('esd.esdMedia.mediaId', null)]
            )
        );

        /** @var ProductCollection $products */
        $products = $this->productRepository->search($criteria, $event->getContext())->getEntities();
        if ($products->count() > 0) {
            $this->esdOrderService->addNewEsdOrders($orderLineItems, $event->getContext(), $products);
        }
    }
}
