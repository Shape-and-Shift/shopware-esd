<?php declare(strict_types=1);

namespace Sas\Esd\Subscriber;

use Sas\Esd\Service\EsdOrderService;
use Sas\Esd\Service\EsdService;
use Sas\Esd\Event\EsdDownloadPaymentStatusPaidEvent;
use Sas\Esd\Event\EsdSerialPaymentStatusPaidEvent;
use Sas\Esd\Utils\EsdMailTemplate;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

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

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * OrderStateChangedSubscriber constructor.
     * @param EntityRepositoryInterface $orderRepository
     * @param EsdService $esdService
     * @param EsdOrderService $esdOrderService
     * @param SystemConfigService $systemConfigService
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        EntityRepositoryInterface $orderRepository,
        EsdService $esdService,
        EsdOrderService $esdOrderService,
        SystemConfigService $systemConfigService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->orderRepository = $orderRepository;
        $this->esdService = $esdService;
        $this->esdOrderService = $esdOrderService;
        $this->systemConfigService = $systemConfigService;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents()
    {
        return [
            'state_enter.order_transaction.state.paid' => 'orderStatePaid',
        ];
    }

    /**
     * @param OrderStateMachineStateChangeEvent $event
     */
    public function orderStatePaid(OrderStateMachineStateChangeEvent $event): void
    {
        $criteria = new Criteria([$event->getOrder()->getId()]);
        $criteria->addAssociation('lineItems.product.esd.esdMedia');
        $criteria->addAssociation('orderCustomer.customer');

        $criteria->addFilter(
            new NotFilter(
                NotFilter::CONNECTION_AND,
                [new EqualsFilter('lineItems.product.esd.esdMedia.mediaId', null)]
            )
        );

        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $event->getContext())->get($event->getOrder()->getId());
        if (!empty($order)) {
            if (!empty($order->getLineItems()) && $order->getAmountTotal() > 0) {
                $orderLineItemIds = array_filter($order->getLineItems()->fmap(static function (OrderLineItemEntity $orderLineItem) {
                    return $orderLineItem->getId();
                }));

                $esdOrders = $this->esdService->getEsdOrderByOrderLineItemIds($orderLineItemIds, $event->getContext());
                if (empty($esdOrders->first())) {
                    $this->esdOrderService->addNewEsdOrders($order, $event->getContext());
                }

                $templateData = $this->esdOrderService->mailTemplateData($order, $event->getContext());

                if ($this->getSystemConfig(EsdMailTemplate::TEMPLATE_DOWNLOAD_SYSTEM_CONFIG_NAME) &&
                    !empty($templateData['esdOrderLineItems'])) {
                    $event = new EsdDownloadPaymentStatusPaidEvent($event->getContext(), $order, $templateData);
                    $this->eventDispatcher->dispatch($event, EsdDownloadPaymentStatusPaidEvent::EVENT_NAME);
                }

                if ($this->getSystemConfig(EsdMailTemplate::TEMPLATE_SERIAL_SYSTEM_CONFIG_NAME) &&
                    !empty($templateData['esdSerials'])) {
                    $event = new EsdSerialPaymentStatusPaidEvent($event->getContext(), $order, $templateData);
                    $this->eventDispatcher->dispatch($event, EsdSerialPaymentStatusPaidEvent::EVENT_NAME);
                }
            }
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    private function getSystemConfig(string $name): bool
    {
        $isSendDownloadConfirmation = $this->systemConfigService->get('SasEsd.config.' . $name);
        if (empty($isSendDownloadConfirmation)) {
            return false;
        }

        return true;
    }
}
