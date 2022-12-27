<?php declare(strict_types=1);

namespace Sas\Esd\Subscriber;

use Sas\Esd\Event\EsdDownloadPaymentStatusPaidDisabledZipEvent;
use Sas\Esd\Event\EsdDownloadPaymentStatusPaidEvent;
use Sas\Esd\Event\EsdSerialPaymentStatusPaidEvent;
use Shopware\Core\Framework\Event\BusinessEventCollector;
use Shopware\Core\Framework\Event\BusinessEventCollectorEvent;
use Shopware\Core\Framework\Event\BusinessEventCollectorResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BusinessEventSubscriber implements EventSubscriberInterface
{
    private BusinessEventCollector $businessEventCollector;

    public function __construct(BusinessEventCollector $businessEventCollector)
    {
        $this->businessEventCollector = $businessEventCollector;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BusinessEventCollectorEvent::NAME => 'onRegisterEvent',
        ];
    }

    public function onRegisterEvent(BusinessEventCollectorEvent $event): void
    {
        $this->defineBusinessEvents($event->getCollection());
    }

    private function defineBusinessEvents(BusinessEventCollectorResponse $collection): void
    {
        $awares = [
            EsdDownloadPaymentStatusPaidEvent::class,
            EsdDownloadPaymentStatusPaidDisabledZipEvent::class,
            EsdSerialPaymentStatusPaidEvent::class,
        ];

        foreach ($awares as $aware) {
            $definition = $this->businessEventCollector->define($aware);
            if (!$definition) {
                return;
            }

            $collection->set($definition->getName(), $definition);
        }
    }
}
