<?php
declare(strict_types=1);

namespace Sas\Esd\Storefront\Controller;

use Sas\Esd\Service\EsdCartService;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\CartLineItemController;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class CartLineItemControllerDecorator extends StorefrontController
{
    public function __construct(
        private readonly CartLineItemController $decoratedController,
        private readonly EsdCartService $esdCartService
    ) {
    }

    public function addLineItems(Cart $cart, RequestDataBag $requestDataBag, Request $request, SalesChannelContext $context): Response
    {
        $lineItems = $requestDataBag->get('lineItems');

        if ($lineItems instanceof RequestDataBag) {
            foreach ($lineItems as $lineItemData) {
                $id = $lineItemData->get('id');
                $type = $lineItemData->get('type');
                $quantity = (int) ($lineItemData->get('quantity') ?? 1);

                if ($type !== LineItem::PRODUCT_LINE_ITEM_TYPE || !$id) {
                    continue;
                }

                $existingLineItem = $cart->getLineItems()->get($id);
                $existingQuantity = $existingLineItem ? $existingLineItem->getQuantity() : 0;
                $totalQuantity = $existingQuantity + $quantity;

                if (!$this->hasEnoughSerials($id, $totalQuantity, $context)) {
                    $this->addFlash(self::DANGER, $this->trans('checkout.esd-serial-not-available'));

                    return $this->createActionResponse($request);
                }
            }
        }

        return $this->decoratedController->addLineItems($cart, $requestDataBag, $request, $context);
    }

    public function addProductByNumber(Request $request, SalesChannelContext $context): Response
    {
        return $this->decoratedController->addProductByNumber($request, $context);
    }

    public function deleteLineItem(Cart $cart, string $id, Request $request, SalesChannelContext $context): Response
    {
        return $this->decoratedController->deleteLineItem($cart, $id, $request, $context);
    }

    public function deleteLineItems(Cart $cart, Request $request, SalesChannelContext $context): Response
    {
        return $this->decoratedController->deleteLineItems($cart, $request, $context);
    }

    public function addPromotion(Cart $cart, Request $request, SalesChannelContext $context): Response
    {
        return $this->decoratedController->addPromotion($cart, $request, $context);
    }

    public function changeQuantity(Cart $cart, string $id, Request $request, SalesChannelContext $context): Response
    {
        $quantity = (int) $request->get('quantity', 1);

        if (!$this->hasEnoughSerials($id, $quantity, $context)) {
            $this->addFlash(self::DANGER, $this->trans('checkout.esd-serial-not-available'));

            return $this->createActionResponse($request);
        }

        return $this->decoratedController->changeQuantity($cart, $id, $request, $context);
    }

    public function updateLineItems(Cart $cart, RequestDataBag $requestDataBag, Request $request, SalesChannelContext $context): Response
    {
        $lineItems = $requestDataBag->get('lineItems');

        if ($lineItems instanceof RequestDataBag) {
            foreach ($lineItems as $lineItemData) {
                $id = $lineItemData->get('id');
                $quantity = (int) ($lineItemData->get('quantity') ?? 0);

                if (!$id || $quantity <= 0) {
                    continue;
                }

                if (!$this->hasEnoughSerials($id, $quantity, $context)) {
                    $this->addFlash(self::DANGER, $this->trans('checkout.esd-serial-not-available'));

                    return $this->createActionResponse($request);
                }
            }
        }

        return $this->decoratedController->updateLineItems($cart, $requestDataBag, $request, $context);
    }

    private function hasEnoughSerials(string $productId, int $quantity, SalesChannelContext $context): bool
    {
        $availableCount = $this->esdCartService->getAvailableSerialCount($productId, $context->getContext());

        // null = not an ESD serial product, allow
        if ($availableCount === null) {
            return true;
        }

        return $quantity <= $availableCount;
    }
}
