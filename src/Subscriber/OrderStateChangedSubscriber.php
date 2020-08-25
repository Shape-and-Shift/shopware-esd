<?php declare(strict_types=1);
namespace Sas\Esd\Subscriber;

use Sas\Esd\Service\EsdOrderService;
use Sas\Esd\Service\EsdService;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderStateChangedSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var EsdService
     */
    private $esdService;

    /**
     * @var EsdService
     */
    private $esdOrderService;

    public function __construct(
        EntityRepositoryInterface $orderRepository,
        EsdService $esdService,
        EsdOrderService $esdOrderService
    ) {
        $this->orderRepository = $orderRepository;
        $this->esdService = $esdService;
        $this->esdOrderService = $esdOrderService;
    }

    public static function getSubscribedEvents()
    {
        return [
            'state_enter.order_transaction.state.paid' => 'orderStatePaid',
        ];
    }

    public function orderStatePaid(OrderStateMachineStateChangeEvent $event)
    {
        $criteria = (new Criteria([$event->getOrder()->getId()]))
            ->addAssociation('lineItems.product.esd.esdMedia');

        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [new EqualsFilter('lineItems.product.esd.esdMedia.mediaId', null)]
            )
        );

        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $event->getContext())->get($event->getOrder()->getId());
        if (!empty($order)) {
            if (!empty($order->getLineItems())) {
                $orderLineItemIds = array_filter($order->getLineItems()->fmap(static function (OrderLineItemEntity $orderLineItem) {
                    return $orderLineItem->getId();
                }));

                $esdOrders = $this->esdService->getEsdOrderByOrderLineItemIds($orderLineItemIds, $event->getContext());
                if (empty($esdOrders->first()) && $order->getAmountTotal() > 0) {
                    $this->esdOrderService->addNewEsdOrders($order->getLineItems(), $event->getContext());
                }
            }
        }
    }
}
