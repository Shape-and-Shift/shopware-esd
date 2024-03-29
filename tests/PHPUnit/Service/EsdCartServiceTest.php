<?php declare(strict_types=1);

namespace Sas\Esd\Tests\Service;

use PHPUnit\Framework\TestCase;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial\EsdSerialCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial\EsdSerialEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Sas\Esd\Exception\ProductNotEnoughSerialException;
use Sas\Esd\Service\EsdCartService;
use Sas\Esd\Tests\Stubs\StaticEntityRepository;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;

class EsdCartServiceTest extends TestCase
{
    private StaticEntityRepository $productRepository;

    private EsdCartService $esdCartService;

    private Context $context;

    public function setUp(): void
    {
        $this->context = Context::createDefaultContext();
    }

    /**
     * @dataProvider dataIsCanCheckoutOrderProvider
     */
    public function testIsCanCheckoutOrder(array $productIds, bool $hasEsd, bool $hasSerial, bool $outOfSerialKey): void
    {
        $entities = $this->getProducts($productIds, $hasEsd, $hasSerial, $outOfSerialKey);
        $this->productRepository = $this->mockProducts($entities);

        $lineItems = new LineItemCollection();

        foreach ($productIds as $productId) {
            $lineItems->add(new LineItem($productId, LineItem::PRODUCT_LINE_ITEM_TYPE, $productId, 1));
        }

        $mockCart = $this->createMock(Cart::class);
        $mockCart->method('getLineItems')->willReturn($lineItems);

        $this->esdCartService = new EsdCartService($this->productRepository);

        static::assertSame($this->esdCartService->isCanCheckoutOrder($mockCart, $this->context), !$outOfSerialKey);
    }

    /**
     * @dataProvider dataCheckProductsProvider
     */
    public function testCheckProductsWithSerialKey(array $productIds, bool $hasEsd, bool $hasSerial, bool $outOfSerialKey): void
    {
        $entities = $this->getProducts($productIds, $hasEsd, $hasSerial, $outOfSerialKey);
        $this->productRepository = $this->mockProducts($entities);
        $this->esdCartService = new EsdCartService($this->productRepository);

        if ($hasEsd) {
            static::assertInstanceOf(EsdEntity::class, $entities[0]->getExtension('esd'));

            if ($hasSerial) {
                static::assertInstanceOf(EsdSerialCollection::class, $entities[0]->getExtension('esd')->getSerial());
            } else {
                static::assertSame($entities[0]->getExtension('esd')->getSerial(), null);
            }
        } else {
            static::assertSame($entities[0]->getExtension('esd'), null);
        }

        if ($outOfSerialKey) {
            static::expectException(ProductNotEnoughSerialException::class);
        }

        $this->esdCartService->checkProductsWithSerialKey($productIds, $this->context);
    }

    public function dataCheckProductsProvider(): array
    {
        return [
            'Test product does not have Esd' => [
                ['id1'], false, false, false,
            ],
            'Test product has Esd but not has serial' => [
                ['id1'], true, false, false,
            ],
            'Test product has Esd and all of serial not assigned' => [
                ['id1'], true, true, false,
            ],
            'Test product has Esd and all of serial assigned so throw exception' => [
                ['id1'], true, true, true,
            ],
        ];
    }

    public function dataIsCanCheckoutOrderProvider(): array
    {
        return [
            'Can checkout order' => [
                ['id1'], true, true, false,
            ],
            'Can not checkout order' => [
                ['id1'], true, true, true,
            ],
        ];
    }

    private function mockProducts(array $entities): StaticEntityRepository
    {
        return new StaticEntityRepository([
            new ProductCollection($entities),
        ]);
    }

    private function getProducts(array $productIds, bool $hasEsd = false, bool $hasSerial = false, bool $outOfSerialKey = false): array
    {
        $products = [];

        foreach ($productIds as $productId) {
            $product = new ProductEntity();
            $product->setId($productId);

            $productEsd = null;

            if ($hasEsd) {
                $productEsd = new EsdEntity();

                $productEsd->setHasSerial($hasSerial);

                if ($hasSerial) {
                    $productEsdSerial = new EsdSerialEntity();
                    $productEsdSerial->setId('id');

                    if ($outOfSerialKey) {
                        $productEsdSerial->setEsdOrder(new EsdOrderEntity());
                    } else {
                        $productEsdSerial->setEsdOrder(null);
                    }

                    $serialsCollection = new EsdSerialCollection([$productEsdSerial]);
                    $productEsd->setSerial($serialsCollection);
                }
            }

            $product->setExtensions([
                'esd' => $productEsd,
            ]);

            $products[] = $product;
        }

        return $products;
    }
}
