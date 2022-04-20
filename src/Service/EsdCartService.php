<?php declare(strict_types=1);

namespace Sas\Esd\Service;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial\EsdSerialCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial\EsdSerialEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Sas\Esd\Exception\ProductNotEnoughSerialException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

class EsdCartService
{
    private EntityRepositoryInterface $productRepository;

    public function __construct(
        EntityRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    public function isCanCheckoutOrder(Cart $cart, Context $context): bool
    {
        $lineItemIds = [];

        try {
            foreach ($cart->getLineItems() as $lineItem) {
                $lineItemIds[] = $lineItem->getId();
            }

            $this->checkProductsWithSerialKey($lineItemIds, $context);

            return true;
        } catch (ProductNotEnoughSerialException $exception) {
            return false;
        }
    }

    public function checkProductsWithSerialKey(array $productIds, Context $context): void
    {
        $criteria = new Criteria($productIds);
        $criteria->addAssociation('esd.serial.esdOrder');

        /** @var $products EntityCollection */
        $products = $this->productRepository->search($criteria, $context)->getEntities();

        foreach ($products as $product) {
            /** @var $productEsd EsdEntity|null */
            $productEsd = $product->getExtension('esd');

            if (!$productEsd instanceof EsdEntity) {
                continue;
            }

            if (!$productEsd->hasSerial()) {
                continue;
            }

            if (!$productEsd->getSerial() instanceof EsdSerialCollection || $productEsd->getSerial()->count() <= 0) {
                continue;
            }

            $availableSerials = $productEsd->getSerial()->filter(function(EsdSerialEntity $serial) {
                return !$serial->getEsdOrder() instanceof EsdOrderEntity;
            });

            if ($availableSerials->count() <= 0) {
                throw new ProductNotEnoughSerialException($product->getId());
            }
        }
    }
}
