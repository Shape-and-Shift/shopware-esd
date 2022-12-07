<?php declare(strict_types=1);

namespace Sas\Esd\Storefront\Controller;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Service\EsdDownloadService;
use Sas\Esd\Service\EsdService;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class DownloadsController extends StorefrontController
{
    private EsdService $esdService;

    private EsdDownloadService $esdDownloadService;

    private GenericPageLoaderInterface $genericLoader;

    private SystemConfigService $systemConfigService;

    public function __construct(
        EsdService $esdService,
        EsdDownloadService $esdDownloadService,
        GenericPageLoaderInterface $genericLoader,
        SystemConfigService $systemConfigService
    ) {
        $this->esdService = $esdService;
        $this->esdDownloadService = $esdDownloadService;
        $this->genericLoader = $genericLoader;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @Route("/account/downloads", name="frontend.account.downloads.page", options={"seo"="false"}, methods={"GET"})
     */
    public function getAccountDownloads(Request $request, SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn();
        $page = $this->genericLoader->load($request, $context);

        $customer = $context->getCustomer();
        if (!$customer instanceof CustomerEntity) {
            throw new CustomerNotLoggedInException();
        }

        $esdOrders = $this->esdService->getEsdOrderListByCustomer($customer, $context);

        /** @var EsdOrderCollection $esdOrdersCollection */
        $esdOrdersCollection = $esdOrders->getEntities();

        $esdMediaByEsdIds = [];
        $esdVideoByEsdIds = [];

        $esdIds = [];
        /** @var EsdOrderEntity $esdOrder */
        foreach ($esdOrdersCollection as $esdOrder) {
            $esdIds[] = $esdOrder->getEsdId();
        }

        if (!empty($esdIds)) {
            $esdMediaByEsdIds = $this->esdService->getEsdMediaByEsdIds($esdIds, $context->getContext());

            if ($this->systemConfigService->get('SasEsd.config.isEsdVideo')) {
                $esdVideoIds = [];
                foreach ($esdMediaByEsdIds as $esdMedia) {
                    $esdVideoIds = array_merge($esdVideoIds, array_keys($esdMedia));
                }

                $esdVideoByEsdIds = $this->esdService->getEsdVideo(
                    $esdVideoIds,
                    $context->getContext()
                );
            }
        }

        $esdOrderIds = array_values($esdOrdersCollection->getIds());
        $downloadLimitItems = $this->esdDownloadService->getDownloadRemainingItems($esdOrderIds, $context->getContext());

        return $this->renderStorefront(
            'storefront/page/account/downloads/index.html.twig',
            [
                'page' => $page,
                'esdOrders' => $esdOrders,
                'downloadLimits' => $this->esdDownloadService->getLimitDownloadNumberList($esdOrdersCollection),
                'downloadLimitItems' => $downloadLimitItems,
                'esdMediaByEsdIds' => $esdMediaByEsdIds,
                'esdVideoMediaByEsdIds' => $esdMediaByEsdIds,
                'esdVideoByEsdIds' => $esdVideoByEsdIds,
            ]
        );
    }

    /**
     * @Route("/account/downloads/remaining", name="frontend.account.downloads.remaining", methods={"GET"}, options={"seo"="false"}, defaults={"XmlHttpRequest": true})
     */
    public function getDownloadRemaining(Request $request, SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn();
        $page = $this->genericLoader->load($request, $context);

        $customer = $context->getCustomer();
        if (!$customer instanceof CustomerEntity) {
            throw new CustomerNotLoggedInException();
        }

        $esdOrders = $this->esdService->getEsdOrderListByCustomer($customer, $context);
        /** @var EsdOrderCollection $esdOrdersCollection */
        $esdOrdersCollection = $esdOrders->getEntities();

        return $this->renderStorefront('@Storefront/storefront/page/account/downloads/table.html.twig', [
            'page' => $page,
            'esdOrders' => $esdOrders,
            'downloadLimits' => $this->esdDownloadService->getLimitDownloadNumberList($esdOrdersCollection),
        ]);
    }

    /**
     * @Route("/account/downloads/item/remaining", name="frontend.account.downloads.item-remaining", methods={"GET"}, options={"seo"="false"}, defaults={"XmlHttpRequest": true})
     */
    public function getItemDownloadRemaining(Request $request, SalesChannelContext $context): Response
    {
        $this->denyAccessUnlessLoggedIn();
        $page = $this->genericLoader->load($request, $context);

        $customer = $context->getCustomer();
        if (!$customer instanceof CustomerEntity) {
            throw new CustomerNotLoggedInException();
        }

        $esdOrders = $this->esdService->getEsdOrderListByCustomer($customer, $context);
        /** @var EsdOrderCollection $esdOrdersCollection */
        $esdOrdersCollection = $esdOrders->getEntities();

        $esdOrderIds = array_values($esdOrdersCollection->getIds());
        $downloadRemainingItems = $this->esdDownloadService->getDownloadRemainingItems($esdOrderIds, $context->getContext());

        return $this->renderStorefront('@Storefront/storefront/page/account/downloads/video-table-detail.html.twig', [
            'page' => $page,
            'esdOrders' => $esdOrders,
            'downloadLimits' => $this->esdDownloadService->getLimitDownloadNumberList($esdOrdersCollection),
            'downloadLimitItems' => $downloadRemainingItems,
        ]);
    }

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

        if (!$context instanceof SalesChannelContext) {
            return;
        }

        if (!$context->getCustomer() instanceof CustomerEntity) {
            return;
        }

        if ($allowGuest || $context->getCustomer()->getGuest() === false) {
            return;
        }

        throw new CustomerNotLoggedInException();
    }
}
