<?php declare(strict_types=1);

namespace Sas\Esd\Storefront\Controller;

use Sas\Esd\Service\EsdCartService;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\CheckoutController;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @RouteScope(scopes={"storefront"})
 */
class CheckoutControllerDecorator extends StorefrontController
{
    private CheckoutController $decoratedController;

    private CartService $cartService;

    private EsdCartService $esdCartService;

    public function __construct(
        CheckoutController $decoratedController,
        CartService $cartService,
        EsdCartService $esdCartService
    ) {
        $this->decoratedController = $decoratedController;
        $this->cartService = $cartService;
        $this->esdCartService = $esdCartService;
    }

    public function cartPage(Request $request, SalesChannelContext $context): Response
    {
        return $this->decoratedController->cartPage($request, $context);
    }

    public function confirmPage(Request $request, SalesChannelContext $context): Response
    {
        return $this->decoratedController->confirmPage($request, $context);
    }

    public function finishPage(Request $request, SalesChannelContext $context, RequestDataBag $dataBag): Response
    {
        return $this->decoratedController->finishPage($request, $context, $dataBag);
    }

    public function order(RequestDataBag $data, SalesChannelContext $context, Request $request): Response
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);

        if (!$this->esdCartService->isCanCheckoutOrder($cart, $context->getContext())) {
            $this->addFlash('danger', 'Failed to checkout order');

            return $this->forwardToRoute('frontend.checkout.confirm.page');
        }

        return $this->decoratedController->order($data, $context, $request);
    }

    public function info(Request $request, SalesChannelContext $context): Response
    {
        return $this->decoratedController->info($request, $context);
    }

    public function offcanvas(Request $request, SalesChannelContext $context): Response
    {
        return $this->decoratedController->offcanvas($request, $context);
    }
}
