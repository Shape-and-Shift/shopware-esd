<?php
declare(strict_types=1);

namespace Sas\Esd\Service;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial\EsdSerialCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial\EsdSerialEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Sas\Esd\Exception\EsdException;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class EsdCartService
{
    /**
     * @param EntityRepository<ProductCollection> $productRepository
     */
    public function __construct(
        private readonly EntityRepository $productRepository
    ) {
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
        } catch (EsdException) {
            return false;
        }
    }

    /**
     * @param array<string> $productIds
     *
     * @throws EsdException
     */
    public function checkProductsWithSerialKey(array $productIds, Context $context): void
    {
        $criteria = new Criteria($productIds);
        $criteria->addAssociation('esd.serial.esdOrder');

        $products = $this->productRepository->search($criteria, $context)->getEntities();

        foreach ($products as $product) {
            $productEsd = $product->getExtension('esd');
            if (!$productEsd instanceof EsdEntity) {
                continue;
            }

            if (!$productEsd->isHasSerial()) {
                continue;
            }

            if (!$productEsd->getSerial() instanceof EsdSerialCollection || $productEsd->getSerial()->count() <= 0) {
                continue;
            }

            $availableSerials = $productEsd->getSerial()->filter(function (EsdSerialEntity $serial) {
                return !$serial->getEsdOrder() instanceof EsdOrderEntity;
            });

            if ($availableSerials->count() <= 0) {
                throw EsdException::productNotEnoughSerialKey($product->getId());
            }
        }
    }
}
