<?php declare(strict_types=1);

namespace Sas\Esd\Storefront\Controller;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Psr\Log\LoggerInterface;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Service\EsdDownloadService;
use Sas\Esd\Service\EsdService;
use Shopware\Core\Checkout\Cart\CartException;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */
class StreamDownloadController extends StorefrontController
{
    public function __construct(
        private readonly FilesystemOperator $filesystemPublic,
        private readonly FilesystemOperator $filesystemPrivate,
        private readonly EsdService $esdService,
        private readonly EsdDownloadService $esdDownloadService,
        private readonly SystemConfigService $systemConfigService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @Route("/esd/download/{esdOrderId}", name="frontend.sas.esd.download", options={"seo"="false"}, methods={"GET"})
     */
    public function downloadByUserLoggedIn(SalesChannelContext $context, string $esdOrderId): Response
    {
        $this->denyAccessUnlessLoggedIn($context);

        $customer = $context->getCustomer();
        if (!$customer instanceof CustomerEntity) {
            throw CartException::customerNotLoggedIn();
        }

        $esdOrder = $this->esdService->getEsdOrderByCustomer($customer, $esdOrderId, $context);
        if (!$esdOrder instanceof EsdOrderEntity) {
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
        $this->denyAccessUnlessLoggedIn($context);

        return $this->streamMediaLineItem($context, $esdOrderId, $mediaId);
    }

    /**
     * @Route("/esd/item/guest/{esdOrderId}/{mediaId}", name="frontend.sas.lineItem.media.url.guest", options={"seo"="false"}, methods={"GET"})
     */
    public function streamMediaLineItemByGuest(SalesChannelContext $context, string $esdOrderId, string $mediaId): ?StreamedResponse
    {
        $esdOrder = $this->esdService->getEsdOrderByGuest($esdOrderId, $context);
        if (!$esdOrder instanceof EsdOrderEntity) {
            throw new NotFoundHttpException('Esd cannot be found');
        }

        return $this->streamMediaLineItem($context, $esdOrderId, $mediaId);
    }

    /**
     * @Route("/esd/video/{esdId}/{mediaId}", name="frontend.sas.esd.video.url", options={"seo"="false"}, methods={"GET"})
     */
    public function streamVideo(SalesChannelContext $context, string $esdId, string $mediaId): ?StreamedResponse
    {
        $this->denyAccessUnlessLoggedIn($context);

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
    protected function denyAccessUnlessLoggedIn(SalesChannelContext $context, bool $allowGuest = false): void
    {
        $customer = $context->getCustomer();
        if (!$customer instanceof CustomerEntity) {
            throw CartException::customerNotLoggedIn();
        }

        if ($allowGuest || $customer->getGuest() === false) {
            return;
        }

        throw CartException::customerNotLoggedIn();
    }

    private function streamMediaLineItem(SalesChannelContext $context, string $esdOrderId, string $mediaId): StreamedResponse
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

        if (!$esdMedia->getMedia() instanceof MediaEntity) {
            throw new NotFoundHttpException('Esd media cannot be found');
        }

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

    private function mediaProcess(MediaEntity $media): StreamedResponse
    {
        $path = $this->esdService->getPathVideoMedia($media);
        $fileSystem = $this->getFileSystem($path);

        return new StreamedResponse(function () use ($fileSystem, $path): void {
            $outputStream = fopen('php://output', 'rb');
            $fileStream = $fileSystem->readStream($path);
            if (\is_resource($outputStream) && \is_resource($fileStream)) {
                stream_copy_to_stream($fileStream, $outputStream);
                fclose($outputStream);
            }
        });
    }

    private function getFileSystem(string $path): FilesystemOperator
    {
        $fileSystem = $this->filesystemPrivate;

        try {
            if ($this->filesystemPublic->read($path)) {
                $fileSystem = $this->filesystemPublic;
            }
        } catch (FilesystemException $e) {
            $this->logger->warning('We could not found media from ' . $path);
        }

        return $fileSystem;
    }

    private function downloadProcess(EsdOrderEntity $esdOrder, SalesChannelContext $context): Response
    {
        if (!$esdOrder->getEsd()) {
            return new Response();
        }

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

        if (!$esdOrder->getOrderLineItem()) {
            return new Response();
        }

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $this->esdService->downloadFileName($esdOrder->getOrderLineItem()->getLabel())
        );

        $filesize = filesize($path);
        if (!\is_int($filesize)) {
            $filesize = 0;
        }
        $response = new Response();
        $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
        $response->headers->set('Content-Type', 'zip');
        $response->headers->set('Content-Disposition', $disposition);
        $response->headers->set('Content-Length', (string) $filesize);
        $response->headers->set('Pragma', 'public');

        $content = file_get_contents($path);
        if (!\is_string($content)) {
            return $response;
        }

        $response->sendHeaders();
        $response->setContent($content);

        flush();
        readfile($path);

        return $response;
    }
}
