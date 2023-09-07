<?php declare(strict_types=1);

namespace Sas\Esd\Subscriber;

use Shopware\Core\Content\Flow\Events\BeforeLoadStorableFlowDataEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderStorerSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'flow.storer.order.criteria.event' => 'handle',
        ];
    }

    public function handle(BeforeLoadStorableFlowDataEvent $event): void
    {
        $criteria = $event->getCriteria();
        $criteria->addAssociation('orderCustomer.customer');
    }
}
