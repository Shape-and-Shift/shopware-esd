<?php declare(strict_types=1);

namespace Sas\Esd\Subscriber;

use Sas\Esd\Message\CompressMediaMessage;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Storefront\Page\Product\ProductPageCriteriaEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ProductSubscriber implements EventSubscriberInterface
{
    private MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_WRITTEN_EVENT => 'onProductsWritten',
            ProductPageCriteriaEvent::class => 'onProductPageCriteria',
        ];
    }

    public function onProductsWritten(EntityWrittenEvent $event): void
    {
        if (empty($event->getIds()[0])) {
            return;
        }

        $message = new CompressMediaMessage();
        $message->setProductId($event->getIds()[0]);

        $this->messageBus->dispatch($message);
    }

    public function onProductPageCriteria(ProductPageCriteriaEvent $event): void
    {
        $criteria = $event->getCriteria();
        $criteria->addAssociation('esd.serial.esdOrder');
    }
}
