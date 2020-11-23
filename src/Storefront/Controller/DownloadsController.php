<?php declare(strict_types=1);

namespace Sas\Esd\Storefront\Controller;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderCollection;
use Sas\Esd\Service\EsdDownloadService;
use Sas\Esd\Service\EsdService;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Store\Services\StoreService;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class DownloadsController extends StorefrontController
{
    /**
     * @var EntityRepositoryInterface
     */
    private $esdOrderRepository;

    /**
     * @var EsdService
     */
    private $esdService;

    /**
     * @var EsdDownloadService
     */
    private $esdDownloadService;

    public function __construct(
        EntityRepositoryInterface $esdOrderRepository,
        EsdService $esdService,
        EsdDownloadService $esdDownloadService
    ) {
        $this->esdOrderRepository = $esdOrderRepository;
        $this->esdService = $esdService;
        $this->esdDownloadService = $esdDownloadService;
    }

    /**
     * @Route("/account/downloads", name="frontend.account.downloads.page", options={"seo"="false"}, methods={"GET"})
     *
     * @throws CustomerNotLoggedInException
     */
    public function getAccountDownloads(SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn();
        $esdOrders = $this->esdService->getEsdOrderListByCustomer($context);
        /** @var EsdOrderCollection $esdOrdersCollection */
        $esdOrdersCollection = $esdOrders->getEntities();

        return $this->renderStorefront(
            'storefront/page/account/downloads/index.html.twig',
            [
                'esdOrders' => $esdOrders,
                'downloadLimits' => $this->esdDownloadService->getLimitDownloadNumberList($esdOrdersCollection),
            ]
        );
    }

    /**
     * @Route("/account/downloads/remaining", name="frontend.account.downloads.remaining", methods={"GET"}, options={"seo"="false"}, defaults={"XmlHttpRequest": true})
     */
    public function getDownloadRemaining(SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn();
        $esdOrders = $this->esdService->getEsdOrderListByCustomer($context);
        /** @var EsdOrderCollection $esdOrdersCollection */
        $esdOrdersCollection = $esdOrders->getEntities();

        return $this->renderStorefront('@Storefront/storefront/page/account/downloads/table.html.twig', [
            'esdOrders' => $esdOrders,
            'downloadLimits' => $this->esdDownloadService->getLimitDownloadNumberList($esdOrdersCollection),
        ]);
    }

    /**
     * @throws CustomerNotLoggedInException
     */
    protected function denyAccessUnlessLoggedIn(bool $allowGuest = false): void
    {
        /** @var RequestStack $requestStack */
        $requestStack = $this->get('request_stack');
        $request = $requestStack->getCurrentRequest();

        if (!$request) {
            throw new CustomerNotLoggedInException();
        }

        /** @var SalesChannelContext|null $context */
        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        if (
            $context
            && $context->getCustomer()
            && (
                $allowGuest === true
                || $context->getCustomer()->getGuest() === false
            )
        ) {
            return;
        }

        throw new CustomerNotLoggedInException();
    }
}
