<?php declare(strict_types=1);

namespace Sas\Esd\Subscriber;

use Sas\Esd\Service\EsdCartService;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemQuantityChangedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Event\ShopwareSalesChannelEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CartSubscriber implements EventSubscriberInterface
{
    private EsdCartService $esdCartService;

    public function __construct(
        EsdCartService $esdCartService
    )
    {
        $this->esdCartService = $esdCartService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeLineItemAddedEvent::class => 'onCheckProductSerial',
            BeforeLineItemQuantityChangedEvent::class => 'onCheckProductSerial',
        ];
    }

    public function onCheckProductSerial(ShopwareSalesChannelEvent $event): void
    {
        if ($event->getLineItem()->getType() !== LineItem::PRODUCT_LINE_ITEM_TYPE) {
            return;
        }

        $lineItem = $event->getLineItem();
        $this->esdCartService->checkProductsWithSerialKey([$lineItem->getId()], $event->getContext());
    }
}
