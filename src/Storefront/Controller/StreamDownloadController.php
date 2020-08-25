<?php declare(strict_types=1);
namespace Sas\Esd\Storefront\Controller;

use League\Flysystem\FilesystemInterface;
use Sas\Esd\Service\EsdService;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
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
    private $filesystemPrivate;

    /**
     * @var EsdService
     */
    private $esdService;

    public function __construct(
        EntityRepositoryInterface $esdOrderRepository,
        FilesystemInterface $filesystemPrivate,
        EsdService $esdService
    ) {
        $this->esdOrderRepository = $esdOrderRepository;
        $this->filesystemPrivate = $filesystemPrivate;
        $this->esdService = $esdService;
    }

    /**
     * @Route("/esd/download/{productId}", name="frontend.sas.esd.download", options={"seo"="false"}, methods={"GET"})
     *
     * @throws CustomerNotLoggedInException
     */
    public function __invoke(SalesChannelContext $context, string $productId)
    {
        $this->denyAccessUnlessLoggedIn();

        $esdOrder = $this->esdService->getEsdOrderByCustomer($productId, $context);
        if (empty($esdOrder)) {
            throw new NotFoundHttpException('Esd cannot be found');
        }

        if (!is_file($this->esdService->getCompressFile($productId))) {
            // Create a zip file for old version
            $this->esdService->compressFiles($productId);
        }

        $fileSystem = $this->filesystemPrivate;
        $path = $this->esdService->getPathCompressFile($productId);
        $response = new StreamedResponse(function () use ($fileSystem, $path) {
            $outputStream = fopen('php://output', 'wb');
            $fileStream = $fileSystem->readStream($path);
            stream_copy_to_stream($fileStream, $outputStream);
        });

        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_ATTACHMENT,
            $this->esdService->downloadFileName($esdOrder->getOrderLineItem()->getLabel())
        );

        $response->headers->set('Content-Type', 'zip');
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
