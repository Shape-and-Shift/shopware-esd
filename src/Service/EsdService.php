<?php declare(strict_types=1);

namespace Sas\Esd\Service;

use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Psr\Log\LoggerInterface;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdVideo\EsdVideoEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Sas\Esd\Event\ReadEsdFileEvent;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EsdService
{
    public const FOLDER_COMPRESS_NAME = 'esd-compress';

    private EntityRepositoryInterface $esdProductRepository;

    private EntityRepositoryInterface $esdOrderRepository;

    private EntityRepositoryInterface $productRepository;

    private UrlGeneratorInterface $urlGenerator;

    private EntityRepositoryInterface $esdVideoRepository;

    private SystemConfigService $systemConfigService;

    private FilesystemInterface $filesystemPublic;

    private FilesystemInterface $filesystemPrivate;

    private LoggerInterface $logger;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EntityRepositoryInterface $esdProductRepository,
        EntityRepositoryInterface $esdOrderRepository,
        EntityRepositoryInterface $productRepository,
        UrlGeneratorInterface $urlGenerator,
        EntityRepositoryInterface $esdVideoRepository,
        SystemConfigService $systemConfigService,
        FilesystemInterface $filesystemPublic,
        FilesystemInterface $filesystemPrivate,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->esdProductRepository = $esdProductRepository;
        $this->esdOrderRepository = $esdOrderRepository;
        $this->productRepository = $productRepository;
        $this->urlGenerator = $urlGenerator;
        $this->esdVideoRepository = $esdVideoRepository;
        $this->systemConfigService = $systemConfigService;
        $this->filesystemPublic = $filesystemPublic;
        $this->filesystemPrivate = $filesystemPrivate;
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function compressFiles(string $productId): void
    {
        if ($this->getSystemConfig('isDisableZipFile')) {
            return;
        }

        $esdMedia = $this->getEsdMediaByProductId($productId, Context::createDefaultContext());
        if (empty($esdMedia)) {
            return;
        }

        $criteria = new Criteria([$productId]);
        /** @var ProductEntity $product */
        $product = $this->productRepository->search($criteria, Context::createDefaultContext())->first();
        if (!$product instanceof ProductEntity) {
            return;
        }

        $this->checkPathFolders();

        $medias = $esdMedia->filter(function (EsdMediaEntity $media) {
            return $media->getMedia() instanceof MediaEntity;
        });

        if (\count($medias) === 0) {
            return;
        }

        $zip = new \ZipArchive();
        $zip->open($this->getCompressFile($productId), \ZipArchive::OVERWRITE | \ZipArchive::CREATE);

        $tempFiles = [];
        /** @var EsdMediaEntity $media */
        foreach ($medias as $media) {
            if (!$media->getMedia() instanceof MediaEntity) {
                continue;
            }

            $filename = $media->getMedia()->getFileName() . '.' . $media->getMedia()->getFileExtension();
            $newfile = $this->getTempFolder() . '/' . $filename;

            $mediaBlob = $this->loadMediaFile($media->getMedia());
            if (!\is_string($mediaBlob)) {
                continue;
            }

            file_put_contents($newfile, $mediaBlob);

            $tempFiles[] = $newfile;

            $zip->addFile($newfile, $filename);
        }

        $zip->close();

        foreach ($tempFiles as $tempFile) {
            unlink($tempFile);
        }
    }

    public function getEsdMediaByProductId(string $productId, Context $context): ?EsdMediaCollection
    {
        $criteria = new Criteria();
        $criteria->addAssociation('esdMedia');
        $criteria->addFilter(new EqualsFilter('productId', $productId));

        /** @var EsdEntity $esd */
        $esd = $this->esdProductRepository->search($criteria, $context)->first();
        if (!$esd instanceof EsdEntity) {
            return null;
        }

        if (!$esd->getEsdMedia() instanceof EsdMediaCollection) {
            return null;
        }

        return $esd->getEsdMedia();
    }

    public function getEsdMediaByEsdIds(array $esdIds, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addAssociation('esdMedia.media');
        $criteria->addFilter(new EqualsAnyFilter('id', $esdIds));

        $esdCollection = $this->esdProductRepository->search($criteria, $context)->getEntities();
        if ($esdCollection->count() === 0) {
            return [];
        }

        $esdMediaByEsdIds = [];
        /** @var EsdEntity $esd */
        foreach ($esdCollection as $esd) {
            if (!$esd->getEsdMedia() instanceof EsdMediaCollection) {
                continue;
            }

            /** @var EsdMediaEntity $esdMedia */
            foreach ($esd->getEsdMedia() as $esdMedia) {
                if (empty($esdMedia->getMedia())) {
                    continue;
                }

                $esdMediaByEsdIds[$esd->getId()][$esdMedia->getId()] = $esdMedia;
            }
        }

        return $esdMediaByEsdIds;
    }

    public function getEsdVideo(array $esdMediaIds, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('esdMediaId', $esdMediaIds));

        $esdVideoByEsdIds = [];
        $esdVideoCollection = $this->esdVideoRepository->search($criteria, $context)->getEntities();
        if ($esdVideoCollection->count() === 0) {
            return [];
        }

        /** @var EsdVideoEntity $esdVideo */
        foreach ($esdVideoCollection as $esdVideo) {
            $esdVideoByEsdIds[$esdVideo->getEsdMediaId()] = $esdVideo;
        }

        return $esdVideoByEsdIds;
    }

    public function getVideoMedia(string $esdId, string $mediaId, Context $context): ?MediaEntity
    {
        if ($this->getMedia($esdId, $mediaId, $context)) {
            return $this->getMedia($esdId, $mediaId, $context)->getMedia();
        }

        return null;
    }

    public function getMediaByLineItemId(string $esdOrderId, Context $context): ?EsdOrderEntity
    {
        $criteria = new Criteria([$esdOrderId]);
        $criteria->addAssociation('orderLineItem');
        $criteria->addAssociation('esd');

        /** @var EsdOrderEntity $esdOrder */
        $esdOrder = $this->esdOrderRepository->search($criteria, $context)->first();

        return $esdOrder;
    }

    public function getMedia(string $esdId, string $mediaId, Context $context): ?EsdMediaEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation('esdMedia');
        $criteria->addFilter(new EqualsFilter('id', $esdId));

        /** @var EsdEntity $esd */
        $esd = $this->esdProductRepository->search($criteria, $context)->first();
        if (!$esd instanceof EsdEntity) {
            return null;
        }

        if (!$esd->getEsdMedia() instanceof EsdMediaCollection) {
            return null;
        }

        $esdMedias = $esd->getEsdMedia()->filter(function (EsdMediaEntity $esdMedia) use ($mediaId) {
            return $esdMedia->getMediaId() === $mediaId;
        });

        $esdMedia = $esdMedias->first();
        if (!$esdMedia instanceof EsdMediaEntity) {
            return null;
        }

        return $esdMedias->first();
    }

    public function getPathVideoMedia(MediaEntity $media): string
    {
        return $this->urlGenerator->getRelativeMediaUrl($media);
    }

    public function getEsdOrderByCustomer(CustomerEntity $customer, string $esdOrderId, SalesChannelContext $context): ?EsdOrderEntity
    {
        $criteria = new Criteria([$esdOrderId]);
        $criteria->addAssociation('orderLineItem.order');
        $criteria->addAssociation('esd');
        $criteria->addFilter(
            new EqualsFilter('orderLineItem.order.orderCustomer.customerId', $customer->getId())
        );

        /** @var EsdOrderEntity $esdOrder */
        $esdOrder = $this->esdOrderRepository->search($criteria, $context->getContext())->first();

        return $esdOrder;
    }

    public function getEsdOrderByGuest(string $esdOrderId, SalesChannelContext $context): ?EsdOrderEntity
    {
        $criteria = new Criteria([$esdOrderId]);
        $criteria->addAssociation('orderLineItem');
        $criteria->addAssociation('esd');

        /** @var EsdOrderEntity $esdOrder */
        $esdOrder = $this->esdOrderRepository->search($criteria, $context->getContext())->first();

        return $esdOrder;
    }

    public function getEsdOrderListByCustomer(CustomerEntity $customer, SalesChannelContext $context): EntitySearchResult
    {
        $criteria = $this->createCriteriaEsdOrder($customer->getId());

        $esdOrders = $this->esdOrderRepository->search($criteria, $context->getContext());
        /** @var EsdOrderEntity $esdOrder */
        foreach ($esdOrders as $esdOrder) {
            if (!$esdOrder->getEsd()->getEsdMedia() instanceof EsdMediaCollection) {
                continue;
            }

            $esdMedias = $esdOrder->getEsd()->getEsdMedia()->filter(function (EsdMediaEntity $esdMedia) {
                return $esdMedia->getMediaId() !== null;
            });

            $esdOrder->getEsd()->setEsdMedia($esdMedias);
        }

        return $esdOrders;
    }

    public function getEsdOrderByOrderLineItemIds(array $ids, Context $context): EsdOrderCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('orderLineItemId', $ids));

        /** @var EsdOrderCollection $esOrders */
        $esOrders = $this->esdOrderRepository->search($criteria, $context)->getEntities();

        return $esOrders;
    }

    public function getCompressFile(string $productId): string
    {
        return $this->getPrivateFolder() . $this->getPathCompressFile($productId);
    }

    public function getPathCompressFile(string $productId): string
    {
        return self::FOLDER_COMPRESS_NAME . "/$productId.zip";
    }

    public function downloadFileName(string $string): string
    {
        return $this->convertFileName($string) . '.zip';
    }

    public function getPrivateFolder(): string
    {
        return \dirname(__DIR__, 5) . '/files/';
    }

    public function getFileSize(string $productId): string
    {
        $this->eventDispatcher->dispatch(new ReadEsdFileEvent($productId));

        $size = filesize($this->getCompressFile($productId));
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $power = $size > 0 ? floor(log($size, 1024)) : 0;

        return number_format(
            $size / pow(1024, $power),
            2,
            '.',
            ','
        ) . ' ' . $units[$power];
    }

    public function getSystemConfig(string $name): bool
    {
        $config = $this->systemConfigService->get('SasEsd.config.' . $name);
        if (empty($config)) {
            return false;
        }

        return true;
    }

    private function checkPathFolders(): void
    {
        $outZipPath = $this->getPrivateFolder() . self::FOLDER_COMPRESS_NAME;
        if (!is_dir($outZipPath)) {
            mkdir($outZipPath);
        }

        $tmpPath = $this->getTempFolder();
        if (!is_dir($tmpPath)) {
            mkdir($tmpPath);
        }
    }

    private function getTempFolder(): string
    {
        return $this->getPrivateFolder() . self::FOLDER_COMPRESS_NAME . '-tmp';
    }

    private function createCriteriaEsdOrder(string $customerId, ?string $productId = null): Criteria
    {
        $criteria = new Criteria();
        $criteria->addAssociation('orderLineItem.order.transactions.stateMachineState');
        $criteria->addAssociation('esd.esdMedia');
        $criteria->addAssociation('serial');

        if ($productId) {
            $criteria->addFilter(new EqualsFilter('esd.productId', $productId));
        }

        $criteria->addFilter(
            new EqualsFilter('orderLineItem.order.orderCustomer.customerId', $customerId)
        );

        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_OR,
                [
                    new EqualsFilter('orderLineItem.order.transactions.stateMachineState.technicalName', 'paid'),
                    new EqualsFilter('orderLineItem.order.amountNet', 0.0),
                ]
            )
        );

        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

        return $criteria;
    }

    private function convertFileName(string $fileName): string
    {
        $string = $fileName;
        $string = str_replace(' ', '-', $string);
        $string = str_replace('ä', 'ae', $string);
        $string = str_replace('ü', 'ue', $string);
        $string = str_replace('ö', 'oe', $string);
        $string = str_replace('Ä', 'Ae', $string);
        $string = str_replace('Ö', 'Oe', $string);
        $string = str_replace('Ü', 'Ue', $string);
        $string = str_replace('ß', 'ss', $string);
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string); // @phpstan-ignore-line

        return preg_replace('/-+/', '-', $string); // @phpstan-ignore-line
    }

    /**
     * @throws \League\Flysystem\FileNotFoundException
     */
    private function loadMediaFile(MediaEntity $media): ?string
    {
        $path = $this->urlGenerator->getRelativeMediaUrl($media);

        try {
            $read = $this->filesystemPublic->read($path);
            if (\is_string($read)) {
                return $read;
            }

            return null;
        } catch (FileNotFoundException $e) {
            return $this->loadPrivateMediaFile($path);
        }
    }

    private function loadPrivateMediaFile(string $path): ?string
    {
        try {
            $read = $this->filesystemPrivate->read($path);
            if (\is_string($read)) {
                return $read;
            }

            return null;
        } catch (FileNotFoundException $e) {
            $this->logger->warning('We could not found media from ' . $path);

            return null;
        }
    }
}
