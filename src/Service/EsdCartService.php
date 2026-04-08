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
        $productQuantities = [];

        try {
            foreach ($cart->getLineItems() as $lineItem) {
                $productQuantities[$lineItem->getId()] = $lineItem->getQuantity();
            }

            $this->checkProductsWithSerialKey($productQuantities, $context);

            return true;
        } catch (EsdException) {
            return false;
        }
    }

    /**
     * @param array<string, int> $productQuantities map of productId => quantity
     *
     * @throws EsdException
     */
    /**
     * @param array<string, int> $productQuantities map of productId => quantity
     *
     * @throws EsdException
     */
    public function checkProductsWithSerialKey(array $productQuantities, Context $context): void
    {
        $criteria = new Criteria(array_keys($productQuantities));
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

            $requestedQuantity = $productQuantities[$product->getId()] ?? 1;

            if ($availableSerials->count() < $requestedQuantity) {
                throw EsdException::productNotEnoughSerialKey($product->getId());
            }
        }
    }

    public function getAvailableSerialCount(string $productId, Context $context): ?int
    {
        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('esd.serial.esdOrder');

        $product = $this->productRepository->search($criteria, $context)->getEntities()->first();
        if (!$product) {
            return null;
        }

        $productEsd = $product->getExtension('esd');
        if (!$productEsd instanceof EsdEntity || !$productEsd->isHasSerial()) {
            return null;
        }

        if (!$productEsd->getSerial() instanceof EsdSerialCollection || $productEsd->getSerial()->count() <= 0) {
            return 0;
        }

        return $productEsd->getSerial()->filter(function (EsdSerialEntity $serial) {
            return !$serial->getEsdOrder() instanceof EsdOrderEntity;
        })->count();
    }
}
