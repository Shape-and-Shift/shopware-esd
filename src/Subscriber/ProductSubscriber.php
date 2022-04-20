<?php declare(strict_types=1);

namespace Sas\Esd\Subscriber;

use Sas\Esd\Service\EsdService;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Storefront\Page\Product\ProductPageCriteriaEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductSubscriber implements EventSubscriberInterface
{
    private EsdService $esdService;

    public function __construct(EsdService $esdService)
    {
        $this->esdService = $esdService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_WRITTEN_EVENT => 'onProductsWritten',
            ProductPageCriteriaEvent::class => 'onProductPageCriteria'
        ];
    }

    public function onProductsWritten(EntityWrittenEvent $event): void
    {
        if (!empty($event->getIds()[0])) {
            $this->esdService->compressFiles($event->getIds()[0]);
        }
    }

    public function onProductPageCriteria(ProductPageCriteriaEvent $event): void
    {
        $criteria = $event->getCriteria();
        $criteria->addAssociation('esd.serial.esdOrder');
    }
}
