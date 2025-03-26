<?php declare(strict_types=1);

namespace Sas\Esd\Subscriber;

use Sas\Esd\Event\EsdDownloadPaymentStatusPaidDisabledZipEvent;
use Sas\Esd\Event\EsdDownloadPaymentStatusPaidEvent;
use Sas\Esd\Event\EsdSerialPaymentStatusPaidEvent;
use Sas\Esd\Service\EsdOrderService;
use Sas\Esd\Service\EsdService;
use Sas\Esd\Utils\EsdMailTemplate;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OrderStateChangedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityRepository $orderRepository,
        private readonly EsdService $esdService,
        private readonly EsdOrderService $esdOrderService,
        private readonly SystemConfigService $systemConfigService,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_enter.order_transaction.state.paid' => 'orderStatePaid',
        ];
    }

    public function orderStatePaid(OrderStateMachineStateChangeEvent $event): void
    {
        $criteria = new Criteria([$event->getOrder()->getId()]);
        $criteria->addAssociation('lineItems.product.esd.esdMedia');
        $criteria->addAssociation('orderCustomer.customer');

        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($criteria, $event->getContext())->first();
        if (empty($order)) {
            return;
        }

        if ($this->esdOrderService->isEsdOrder($order)
            && !empty($order->getLineItems())
            && $order->getAmountTotal() > 0
        ) {
            $orderLineItemIds = array_filter($order->getLineItems()->fmap(static function (OrderLineItemEntity $orderLineItem) {
                return $orderLineItem->getId();
            }));

            $esdOrders = $this->esdService->getEsdOrderByOrderLineItemIds($orderLineItemIds, $event->getContext());
            if (empty($esdOrders->first())) {
                $this->esdOrderService->addNewEsdOrders($order, $event->getContext());
            }

            $templateData = $this->esdOrderService->mailTemplateData($order, $event->getContext());

            if (!empty($templateData['esdOrderLineItems'])) {
                if ($this->getSystemConfig(EsdMailTemplate::TEMPLATE_DOWNLOAD_DISABLED_ZIP_SYSTEM_CONFIG_NAME)) {
                    $event = new EsdDownloadPaymentStatusPaidDisabledZipEvent($event->getContext(), $order, $templateData);
                    $this->eventDispatcher->dispatch($event, EsdDownloadPaymentStatusPaidDisabledZipEvent::EVENT_NAME);
                } else {
                    $event = new EsdDownloadPaymentStatusPaidEvent($event->getContext(), $order, $templateData);
                    $this->eventDispatcher->dispatch($event, EsdDownloadPaymentStatusPaidEvent::EVENT_NAME);
                }
            }

            if (!empty($templateData['esdSerials'])) {
                $event = new EsdSerialPaymentStatusPaidEvent($event->getContext(), $order, $templateData);
                $this->eventDispatcher->dispatch($event, EsdSerialPaymentStatusPaidEvent::EVENT_NAME);
            }
        }
    }

    private function getSystemConfig(string $name): bool
    {
        $config = $this->systemConfigService->get('SasEsd.config.' . $name);
        if (empty($config)) {
            return false;
        }

        return true;
    }
}
