<?php declare(strict_types=1);

namespace Sas\Esd\Storefront\Controller;

use League\Flysystem\FilesystemInterface;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Message\CompressMediaMessage;
use Sas\Esd\Service\EsdDownloadService;
use Sas\Esd\Service\EsdService;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class StreamDownloadController extends StorefrontController
{
    private EntityRepositoryInterface $esdOrderRepository;

    private FilesystemInterface $filesystemPublic;

    private EsdService $esdService;

    private EsdDownloadService $esdDownloadService;

    private SystemConfigService $systemConfigService;

    private MessageBusInterface $messageBus;

    public function __construct(
        EntityRepositoryInterface $esdOrderRepository,
        FilesystemInterface $filesystemPublic,
        EsdService $esdService,
        EsdDownloadService $esdDownloadService,
        SystemConfigService $systemConfigService,
        MessageBusInterface $messageBus
    ) {
        $this->esdOrderRepository = $esdOrderRepository;
        $this->filesystemPublic = $filesystemPublic;
        $this->esdService = $esdService;
        $this->esdDownloadService = $esdDownloadService;
        $this->systemConfigService = $systemConfigService;
        $this->messageBus = $messageBus;
    }

    /**
     * @Route("/esd/download/{esdOrderId}", name="frontend.sas.esd.download", options={"seo"="false"}, methods={"GET"})
     */
    public function downloadByUserLoggedIn(SalesChannelContext $context, string $esdOrderId): Response
    {
        $this->denyAccessUnlessLoggedIn();

        $esdOrder = $this->esdService->getEsdOrderByCustomer($esdOrderId, $context);
        if (empty($esdOrder)) {
            throw new NotFoundHttpException('Esd cannot be found');
        }

        return $this->downloadProcess($esdOrder, $context);
    }

    /**
     * @Route("/esd/download/guest/{esdOrderId}", name="frontend.sas.esd.download.guest", options={"seo"="false"}, methods={"GET"})
     */
    public function downloadByGuest(SalesChannelContext $context, string $esdOrderId): Response
    {
        $esdOrder = $this->esdService->getEsdOrderByGuest($esdOrderId, $context);
        if (empty($esdOrder)) {
            throw new NotFoundHttpException('Esd cannot be found');
        }

        return $this->downloadProcess($esdOrder, $context);
    }

    /**
     * @Route("/esd/item/{esdOrderId}/{mediaId}", name="frontend.sas.lineItem.media.url", options={"seo"="false"}, methods={"GET"})
     */
    public function streamMediaLineItemByUser(SalesChannelContext $context, string $esdOrderId, string $mediaId): ?StreamedResponse
    {
        $this->denyAccessUnlessLoggedIn();

        return $this->streamMediaLineItem($context, $esdOrderId, $mediaId);
    }

    /**
     * @Route("/esd/item/guest/{esdOrderId}/{mediaId}", name="frontend.sas.lineItem.media.url.guest", options={"seo"="false"}, methods={"GET"})
     */
    public function streamMediaLineItemByGuest(SalesChannelContext $context, string $esdOrderId, string $mediaId): ?StreamedResponse
    {
        $esdOrder = $this->esdService->getEsdOrderByGuest($esdOrderId, $context);
        if (empty($esdOrder)) {
            throw new NotFoundHttpException('Esd cannot be found');
        }

        return $this->streamMediaLineItem($context, $esdOrderId, $mediaId);
    }

    /**
     * @Route("/esd/video/{esdId}/{mediaId}", name="frontend.sas.esd.video.url", options={"seo"="false"}, methods={"GET"})
     */
    public function streamVideo(SalesChannelContext $context, string $esdId, string $mediaId): ?StreamedResponse
    {
        $this->denyAccessUnlessLoggedIn();

        $esdVideoPath = $this->esdService->getVideoMedia($esdId, $mediaId, $context->getContext());
        if (empty($esdVideoPath)) {
            throw new NotFoundHttpException('Esd video cannot be found');
        }

        $response = $this->mediaProcess($esdVideoPath);
        $response->headers->set('Content-Type', $esdVideoPath->getMimeType());

        return $response;
    }

    public function getEsdOrder(string $esdId, SalesChannelContext $context): EsdOrderEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation('orderLineItem.order');
        $criteria->addAssociation('esd');
        $criteria->addFilter(new EqualsFilter('esdId', $esdId));
        $criteria->addFilter(
            new EqualsFilter('orderLineItem.order.orderCustomer.customerId', $context->getCustomer()->getId())
        );

        /** @var EsdOrderEntity $esdOrder */
        $esdOrder = $this->esdOrderRepository->search($criteria, $context->getContext())->first();

        return $esdOrder;
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

    private function streamMediaLineItem(SalesChannelContext $context, string $esdOrderId, string $mediaId): ?StreamedResponse
    {
        $esdOrder = $this->esdService->getMediaByLineItemId($esdOrderId, $context->getContext());
        if (empty($esdOrder)) {
            throw new NotFoundHttpException('Cannot found this esd order');
        }

        $esdMedia = $this->esdService->getMedia($esdOrder->getEsdId(), $mediaId, $context->getContext());
        if (empty($esdMedia)) {
            throw new NotFoundHttpException('Esd media cannot be found');
        }

        if (!$this->systemConfigService->get('SasEsd.config.isEsdVideo')) {
            $this->esdDownloadService->checkMediaDownloadHistory($esdOrderId, $esdMedia, $esdOrder, $context->getContext());
        }

        $this->esdDownloadService->addMediaDownloadHistory($esdOrderId, $esdMedia->getId(), $context->getContext());

        $esdMediaPath = $esdMedia->getMedia();
        $response = $this->mediaProcess($esdMediaPath);
        $response->headers->set('Content-Type', $esdMediaPath->getMimeType());

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $esdMediaPath->getFileName() . '.' . $esdMediaPath->getFileExtension()
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    private function mediaProcess(MediaEntity $media): ?StreamedResponse
    {
        $fileSystem = $this->filesystemPublic;
        $path = $this->esdService->getPathVideoMedia($media);

        return new StreamedResponse(function () use ($fileSystem, $path): void {
            $outputStream = fopen('php://output', 'rb');
            $fileStream = $fileSystem->readStream($path);
            stream_copy_to_stream($fileStream, $outputStream);
            fclose($outputStream);
        });
    }

    private function downloadProcess(EsdOrderEntity $esdOrder, SalesChannelContext $context): Response
    {
        $productId = $esdOrder->getEsd()->getProductId();

        if (!is_file($this->esdService->getCompressFile($productId))) {
            // Create a zip file for old version
            $this->esdService->compressFiles($productId);
            $message = new CompressMediaMessage();
            $message->setProductId($productId);

            $this->messageBus->dispatch($message);
        }

        if (!$this->systemConfigService->get('SasEsd.config.isEsdVideo')) {
            $this->esdDownloadService->checkLimitDownload($esdOrder);
            $this->esdDownloadService->addDownloadHistory($esdOrder, $context->getContext());
        }

        $path = $this->esdService->getCompressFile($productId);

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $this->esdService->downloadFileName($esdOrder->getOrderLineItem()->getLabel())
        );

        $response = new Response();
        $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
        $response->headers->set('Content-Type', 'zip');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Length', filesize($path));
        $response->headers->set('Pragma', 'public');

        $response->sendHeaders();
        $response->setContent(file_get_contents($path));

        flush();
        readfile($path);

        return $response;
    }
}
