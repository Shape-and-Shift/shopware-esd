<?php declare(strict_types=1);
namespace Sas\Esd\Storefront\Controller;

use League\Flysystem\FilesystemInterface;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class StreamDownloadController extends StorefrontController
{
    /**
     * @var EntityRepositoryInterface
     */
    private $esdOrderRepository;
    /**
     * @var FilesystemInterface
     */
    private $filesystemPublic;
    /**
     * @var FilesystemInterface
     */
    private $filesystemPrivate;
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    public function __construct(
        EntityRepositoryInterface $esdOrderRepository,
        FilesystemInterface $filesystemPublic,
        FilesystemInterface $filesystemPrivate,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->esdOrderRepository = $esdOrderRepository;
        $this->filesystemPublic = $filesystemPublic;
        $this->filesystemPrivate = $filesystemPrivate;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @Route("/esd/download/{id}", name="frontend.sas.esd.download", options={"seo"="false"}, methods={"GET"})
     *
     * @throws CustomerNotLoggedInException
     */
    public function __invoke(SalesChannelContext $context, string $id)
    {
        $this->denyAccessUnlessLoggedIn();

        $criteria = new Criteria([$id]);
        $criteria->addFilter(new EqualsFilter('orderLineItem.order.orderCustomer.customerId', $context->getCustomer()->getId()));
        $criteria->addFilter(new EqualsFilter('orderLineItem.order.transactions.stateMachineState.technicalName', 'paid'));
        $criteria->addAssociation('esd.media');
        $criteria->addAssociation('orderLineItem.order.transactions.stateMachineState');

        /** @var EsdOrderEntity $item */
        $item = $this->esdOrderRepository->search($criteria, $context->getContext())->first();
        if ($item === null) {
            throw new NotFoundHttpException('Esd cannot be found');
        }

        $esd = $item->getEsd();
        $fileSystem = $esd->getMedia()->isPrivate() ? $this->filesystemPrivate : $this->filesystemPublic;

        $path = $this->urlGenerator->getRelativeMediaUrl($esd->getMedia());

        $response = new StreamedResponse(function () use ($fileSystem, $path) {
            $outputStream = fopen('php://output', 'wb');
            $fileStream = $fileSystem->readStream($path);
            stream_copy_to_stream($fileStream, $outputStream);
        });

        if ($esd->getMedia()->getMimeType()) {
            $response->headers->set('Content-Type', $esd->getMedia()->getMimeType());
        }

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $esd->getMedia()->getFileName() . '.' . $esd->getMedia()->getFileExtension()
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
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
