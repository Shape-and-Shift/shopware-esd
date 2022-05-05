<?php declare(strict_types=1);

namespace Sas\Esd\Service;

use Sas\Esd\Event\EsdDownloadPaymentStatusPaidDisabledZipEvent;
use Sas\Esd\Event\EsdDownloadPaymentStatusPaidEvent;
use Sas\Esd\Event\EsdSerialPaymentStatusPaidEvent;
use Sas\Esd\Utils\EsdMailTemplate;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EsdMailService
{
    private EntityRepositoryInterface $orderRepository;

    private EsdOrderService $esdOrderService;

    private SystemConfigService $systemConfigService;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EntityRepositoryInterface $orderRepository,
        EsdOrderService $esdOrderService,
        SystemConfigService $systemConfigService,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->orderRepository = $orderRepository;
        $this->esdOrderService = $esdOrderService;
        $this->systemConfigService = $systemConfigService;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function sendMailDownload(string $orderId, Context $context): void
    {
        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($this->getCriteria($orderId), $context)->get($orderId);
        if (!empty($order)) {
            $templateData = $this->esdOrderService->mailTemplateData($order, $context);

            if ($this->getSystemConfig(EsdMailTemplate::TEMPLATE_DOWNLOAD_DISABLED_ZIP_SYSTEM_CONFIG_NAME)
                && !empty($templateData['esdOrderLineItems'])) {
                $event = new EsdDownloadPaymentStatusPaidDisabledZipEvent($context, $order, $templateData);
                $this->eventDispatcher->dispatch($event, EsdDownloadPaymentStatusPaidDisabledZipEvent::EVENT_NAME);

                return;
            }

            if ($this->getSystemConfig(EsdMailTemplate::TEMPLATE_DOWNLOAD_SYSTEM_CONFIG_NAME)
                && !empty($templateData['esdOrderLineItems'])) {
                $event = new EsdDownloadPaymentStatusPaidEvent($context, $order, $templateData);
                $this->eventDispatcher->dispatch($event, EsdDownloadPaymentStatusPaidEvent::EVENT_NAME);
            }
        }
    }

    public function sendMailSerial(string $orderId, Context $context): void
    {
        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($this->getCriteria($orderId), $context)->get($orderId);
        if (!empty($order)) {
            $templateData = $this->esdOrderService->mailTemplateData($order, $context);
            if ($this->getSystemConfig(EsdMailTemplate::TEMPLATE_SERIAL_SYSTEM_CONFIG_NAME)
                && !empty($templateData['esdSerials'])) {
                $event = new EsdSerialPaymentStatusPaidEvent($context, $order, $templateData);
                $this->eventDispatcher->dispatch($event, EsdSerialPaymentStatusPaidEvent::EVENT_NAME);
            }
        }
    }

    public function enableMailButtons(string $orderId, Context $context): array
    {
        $buttons['download'] = false;
        $buttons['serial'] = false;

        /** @var OrderEntity|null $order */
        $order = $this->orderRepository->search($this->getCriteria($orderId), $context)->get($orderId);
        if (!$order) {
            return $buttons;
        }

        if (!$this->esdOrderService->isEsdOrder($order)) {
            return $buttons;
        }

        $templateData = $this->esdOrderService->mailTemplateData($order, $context);
        if ($this->getSystemConfig(EsdMailTemplate::TEMPLATE_DOWNLOAD_SYSTEM_CONFIG_NAME)
            && !empty($templateData['esdOrderLineItems'])) {
            $buttons['download'] = true;
        }

        if ($this->getSystemConfig(EsdMailTemplate::TEMPLATE_DOWNLOAD_DISABLED_ZIP_SYSTEM_CONFIG_NAME)
            && !empty($templateData['esdOrderLineItems'])) {
            $buttons['download'] = true;
        }

        if ($this->getSystemConfig(EsdMailTemplate::TEMPLATE_SERIAL_SYSTEM_CONFIG_NAME)
            && !empty($templateData['esdSerials'])) {
            $buttons['serial'] = true;
        }

        return $buttons;
    }

    public function getSystemConfig(string $name): bool
    {
        $config = $this->systemConfigService->get('SasEsd.config.' . $name);
        if (empty($config)) {
            return false;
        }

        return true;
    }

    private function getCriteria(string $orderId): Criteria
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('lineItems.product.esd.esdMedia');
        $criteria->addAssociation('orderCustomer.customer');

        return $criteria;
    }
}
