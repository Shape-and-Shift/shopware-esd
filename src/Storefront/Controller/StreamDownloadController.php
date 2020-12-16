<?php declare(strict_types=1);

namespace Sas\Esd\Storefront\Controller;

use League\Flysystem\FilesystemInterface;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Service\EsdDownloadService;
use Sas\Esd\Service\EsdService;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
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
    private $filesystemPrivate;

    /**
     * @var FilesystemInterface
     */
    private $filesystemPublic;

    /**
     * @var EsdService
     */
    private $esdService;

    /**
     * @var EsdDownloadService
     */
    private $esdDownloadService;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(
        EntityRepositoryInterface $esdOrderRepository,
        FilesystemInterface $filesystemPrivate,
        FilesystemInterface $filesystemPublic,
        EsdService $esdService,
        EsdDownloadService $esdDownloadService,
        SystemConfigService $systemConfigService
    ) {
        $this->esdOrderRepository = $esdOrderRepository;
        $this->filesystemPrivate = $filesystemPrivate;
        $this->filesystemPublic = $filesystemPublic;
        $this->esdService = $esdService;
        $this->esdDownloadService = $esdDownloadService;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @Route("/esd/download/{esdOrderId}", name="frontend.sas.esd.download", options={"seo"="false"}, methods={"GET"})
     */
    public function downloadByUserLoggedIn(SalesChannelContext $context, string $esdOrderId): void
    {
        $this->denyAccessUnlessLoggedIn();

        $esdOrder = $this->esdService->getEsdOrderByCustomer($esdOrderId, $context);
        if (empty($esdOrder)) {
            throw new NotFoundHttpException('Esd cannot be found');
        }

        $this->downloadProcess($esdOrder, $context);
    }

    /**
     * @Route("/esd/download/guest/{esdOrderId}", name="frontend.sas.esd.download.guest", options={"seo"="false"}, methods={"GET"})
     *
     * @return StreamedResponse
     */
    public function downloadByGuest(SalesChannelContext $context, string $esdOrderId): void
    {
        $esdOrder = $this->esdService->getEsdOrderByGuest($esdOrderId, $context);
        if (empty($esdOrder)) {
            throw new NotFoundHttpException('Esd cannot be found');
        }

        $this->downloadProcess($esdOrder, $context);
    }

    /**
     * @Route("/esd/media/{esdId}/{mediaId}", name="frontend.sas.esd.media.url", options={"seo"="false"}, methods={"GET"})
     *
     * @return StreamedResponse
     */
    public function streamMedia(SalesChannelContext $context, string $esdId, string $mediaId): ?StreamedResponse
    {
        $this->denyAccessUnlessLoggedIn();

        $esdVideoPath = $this->esdService->getVideoMedia($esdId, $mediaId, $context->getContext());
        if (empty($esdVideoPath)) {
            throw new NotFoundHttpException('Esd media cannot be found');
        }

        $response = $this->mediaProcess($esdVideoPath);
        $response->headers->set('Content-Type', $esdVideoPath->getMimeType());

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $esdVideoPath->getFileName() . '.' . $esdVideoPath->getFileExtension()
        );
        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @Route("/esd/video/{esdId}/{mediaId}", name="frontend.sas.esd.video.url", options={"seo"="false"}, methods={"GET"})
     *
     * @return StreamedResponse
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

    private function mediaProcess(MediaEntity $media): ?StreamedResponse
    {
        $fileSystem = $this->filesystemPublic;
        $path = $this->esdService->getPathVideoMedia($media);
        $response = new StreamedResponse(function () use ($fileSystem, $path): void {
            $outputStream = fopen('php://output', 'rb');
            $fileStream = $fileSystem->readStream($path);
            stream_copy_to_stream($fileStream, $outputStream);
            fclose($outputStream);
        });

        return $response;
    }

    private function downloadProcess(EsdOrderEntity $esdOrder, SalesChannelContext $context): void
    {
        $productId = $esdOrder->getEsd()->getProductId();

        if (!is_file($this->esdService->getCompressFile($productId))) {
            // Create a zip file for old version
            $this->esdService->compressFiles($productId);
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

        header('Content-Type: zip');
        header('Cache-Control: no-cache, must-revalidate');
        header('Content-Disposition:' . $disposition);
        header('Content-Length: ' . filesize($path));
        header('Pragma: public');
        flush();
        readfile($path);
    }
}
