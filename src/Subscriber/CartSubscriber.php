<?php declare(strict_types=1);

namespace Sas\Esd\Subscriber;

use Sas\Esd\Service\EsdCartService;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemQuantityChangedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Context;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartSubscriber implements EventSubscriberInterface
{
    private EsdCartService $esdCartService;

    public function __construct(
        EsdCartService $esdCartService
    ) {
        $this->esdCartService = $esdCartService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeLineItemAddedEvent::class => 'onBeforeLineItemAdded',
            BeforeLineItemQuantityChangedEvent::class => 'onBeforeLineItemQuantityChanged',
        ];
    }

    public function onBeforeLineItemAdded(BeforeLineItemAddedEvent $event): void
    {
        $this->checkProductSerial($event->getLineItem(), $event->getContext());
    }

    public function onBeforeLineItemQuantityChanged(BeforeLineItemQuantityChangedEvent $event): void
    {
        $this->checkProductSerial($event->getLineItem(), $event->getContext());
    }

    private function checkProductSerial(LineItem $lineItem, Context $context): void
    {
        if ($lineItem->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
            return;
        }

        $this->esdCartService->checkProductsWithSerialKey([$lineItem->getId()], $context);
    }
}
