<?php declare(strict_types=1);

namespace Sas\Esd\Checkout\Cart\Subscriber;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Sas\Esd\Event\EsdDownloadPaymentStatusPaidDisabledZipEvent;
use Sas\Esd\Event\EsdDownloadPaymentStatusPaidEvent;
use Sas\Esd\Event\EsdSerialPaymentStatusPaidEvent;
use Sas\Esd\Service\EsdOrderService;
use Sas\Esd\Utils\EsdMailTemplate;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class OrderPlacedSubscriber
{
    public function __construct(
        private readonly EntityRepository $productRepository,
        private readonly EsdOrderService $esdOrderService,
        private readonly SystemConfigService $systemConfigService,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function __invoke(CheckoutOrderPlacedEvent $event): void
    {
        $orderLineItems = $event->getOrder()->getLineItems();

        if ($orderLineItems === null) {
            return;
        }

        $productIds = array_filter($orderLineItems->fmap(static function (OrderLineItemEntity $orderLineItem) {
            return $orderLineItem->getProductId();
        }));

        if (empty($productIds)) {
            return;
        }

        $criteria = new Criteria($productIds);
        $criteria->addAssociation('esd.esdMedia');

        /** @var ProductCollection $products */
        $products = $this->productRepository->search($criteria, $event->getContext())->getEntities();

        $esdProducts = new ProductCollection();
        /** @var ProductEntity $product */
        foreach ($products as $product) {
            $esd = $product->getExtension('esd');
            if (!$esd instanceof EsdEntity) {
                continue;
            }

            if (!$esd->getEsdMedia() instanceof EsdMediaCollection) {
                continue;
            }

            $esdMedias = $esd->getEsdMedia()->filter(function (EsdMediaEntity $esdMedia) {
                return $esdMedia->getMediaId() !== null;
            });

            if (empty($esdMedias->getElements())) {
                continue;
            }

            $esd->setEsdMedia($esdMedias);
            $esdProducts->add($product);
        }

        if ($esdProducts->count() > 0) {
            $this->esdOrderService->addNewEsdOrders($event->getOrder(), $event->getContext(), $products);
            $templateData = $this->esdOrderService->mailTemplateData($event->getOrder(), $event->getContext());

            if (!empty($templateData['esdOrderLineItems'])) {
                if ($this->getSystemConfig(EsdMailTemplate::TEMPLATE_DOWNLOAD_DISABLED_ZIP_SYSTEM_CONFIG_NAME)) {
                    $event = new EsdDownloadPaymentStatusPaidDisabledZipEvent(
                        $event->getContext(),
                        $event->getOrder(),
                        $templateData
                    );
                    $this->eventDispatcher->dispatch($event, EsdDownloadPaymentStatusPaidDisabledZipEvent::EVENT_NAME);
                } else {
                    $event = new EsdDownloadPaymentStatusPaidEvent($event->getContext(), $event->getOrder(), $templateData);
                    $this->eventDispatcher->dispatch($event, EsdDownloadPaymentStatusPaidEvent::EVENT_NAME);
                }
            }

            if (!empty($templateData['esdSerials'])) {
                $event = new EsdSerialPaymentStatusPaidEvent($event->getContext(), $event->getOrder(), $templateData);
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
