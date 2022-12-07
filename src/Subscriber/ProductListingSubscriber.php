<?php declare(strict_types=1);

namespace Sas\Esd\Subscriber;

use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductListingSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCriteriaEvent::class => 'onProductListingCriteria',
        ];
    }

    public function onProductListingCriteria(ProductListingCriteriaEvent $event): void
    {
        $criteria = $event->getCriteria();
        $criteria->addAssociation('esd.serial.esdOrder');
    }
}
