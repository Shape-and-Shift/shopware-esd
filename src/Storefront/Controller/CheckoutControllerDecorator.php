<?php declare(strict_types=1);

namespace Sas\Esd\Storefront\Controller;

use Sas\Esd\Service\EsdCartService;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\CheckoutController;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class CheckoutControllerDecorator extends StorefrontController
{
    public function __construct(
        private readonly CheckoutController $decoratedController,
        private readonly CartService $cartService,
        private readonly EsdCartService $esdCartService
    ) {
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
