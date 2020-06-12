<?php declare(strict_types=1);

namespace Sas\Esd\Storefront\Controller;

use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\RequestStack;
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

    public function __construct(EntityRepositoryInterface $esdOrderRepository)
    {
        $this->esdOrderRepository = $esdOrderRepository;
    }

    /**
     * @Route("/account/downloads", name="frontend.account.downloads.page", options={"seo"="false"}, methods={"GET"})
     *
     * @throws CustomerNotLoggedInException
     */
    public function __invoke(SalesChannelContext $context)
    {
        $this->denyAccessUnlessLoggedIn();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('orderLineItem.order.orderCustomer.customerId', $context->getCustomer()->getId()));

        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_OR,
                [
                    new EqualsFilter('orderLineItem.order.transactions.stateMachineState.technicalName', 'paid'),
                    new EqualsFilter('orderLineItem.order.amountNet', 0.0)
                ]
            )
        );

        $criteria->addAssociation('esd.media');
        $criteria->addAssociation('serial');
        $criteria->addAssociation('orderLineItem.order.transactions.stateMachineState');

        $criteria->addSorting(
            new FieldSorting('orderLineItem.createdAt', FieldSorting::DESCENDING)
        );

        $items = $this->esdOrderRepository->search($criteria, $context->getContext());

        return $this->renderStorefront(
            'storefront/page/account/downloads/index.html.twig', [
                'items' => $items
            ]
        );
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
