<?php declare(strict_types=1);

namespace Sas\Esd\Subscriber;

use Sas\Esd\Event\EsdDownloadPaymentStatusPaidEvent;
use Sas\Esd\Event\EsdSerialPaymentStatusPaidEvent;
use Shopware\Core\Framework\Event\BusinessEventCollector;
use Shopware\Core\Framework\Event\BusinessEventCollectorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BusinessEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var BusinessEventCollector
     */
    private $businessEventCollector;

    public function __construct(BusinessEventCollector $businessEventCollector)
    {
        $this->businessEventCollector = $businessEventCollector;
    }

    public static function getSubscribedEvents()
    {
        return [
            BusinessEventCollectorEvent::NAME => 'onRegisterEvent',
        ];
    }

    public function onRegisterEvent(BusinessEventCollectorEvent $event): void
    {
        $downloadDefinition = $this->businessEventCollector->define(EsdDownloadPaymentStatusPaidEvent::class);
        if ($downloadDefinition) {
            $event->getCollection()->set(EsdDownloadPaymentStatusPaidEvent::EVENT_NAME, $downloadDefinition);
        }

        $serialDefinition = $this->businessEventCollector->define(EsdSerialPaymentStatusPaidEvent::class);
        if ($serialDefinition) {
            $event->getCollection()->set(EsdSerialPaymentStatusPaidEvent::EVENT_NAME, $serialDefinition);
        }
    }
}
