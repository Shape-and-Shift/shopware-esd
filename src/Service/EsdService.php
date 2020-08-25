<?php declare(strict_types=1);
namespace Sas\Esd\Service;

use League\Flysystem\FilesystemInterface;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Shopware\Core\Content\Media\Pathname\UrlGeneratorInterface;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class EsdService
{
    const FOLDER_COMPRESS_NAME = 'esd-compress';

    /**
     * @var EntityRepositoryInterface
     */
    private $esdProductRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $esdOrderRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var FilesystemInterface
     */
    private $filesystemPrivate;

    public function __construct(
        EntityRepositoryInterface $esdProductRepository,
        EntityRepositoryInterface $esdOrderRepository,
        EntityRepositoryInterface $productRepository,
        UrlGeneratorInterface $urlGenerator,
        FilesystemInterface $filesystemPrivate
    ) {
        $this->esdProductRepository = $esdProductRepository;
        $this->esdOrderRepository = $esdOrderRepository;
        $this->productRepository = $productRepository;
        $this->urlGenerator = $urlGenerator;
        $this->filesystemPrivate = $filesystemPrivate;
    }

    public function compressFiles($productId): bool
    {
        $esdMedia = $this->getEsdMediaByProductId($productId, Context::createDefaultContext());
        if (empty($esdMedia)) {
            return false;
        }

        if (!$this->checkExistAllFiles($esdMedia)) {
            return false;
        }

        $criteria = new Criteria([$productId]);
        /** @var ProductEntity $product */
        $product = $this->productRepository->search($criteria, Context::createDefaultContext())->first();
        if (empty($product)) {
            return false;
        }

        $outZipPath = $this->getPrivateFolder() . self::FOLDER_COMPRESS_NAME;
        if (!is_dir($outZipPath)) {
            mkdir($outZipPath, 0777);
        }

        $zip = new \ZipArchive;
        $zip->open($this->getCompressFile($productId), \ZipArchive::OVERWRITE | \ZipArchive::CREATE);

        foreach ($esdMedia as $media) {
            if (empty($media->getMedia())) {
                continue;
            }

            $filePath = $this->urlGenerator->getRelativeMediaUrl($media->getMedia());
            $folderName = $this->convertFileName($product->getName());
            $localName = $folderName . '/' . $media->getMedia()->getFileName() . '.' . $media->getMedia()->getFileExtension();

            $zip->addFile($filePath, $localName);
        }

        $zip->close();

        return true;
    }

    public function getEsdMediaByProductId(string $productId, Context $context): ?EsdMediaCollection
    {
        $criteria = new Criteria();
        $criteria->addAssociation('esdMedia');
        $criteria->addFilter(new EqualsFilter('productId', $productId));

        /** @var EsdEntity $esd */
        $esd = $this->esdProductRepository->search($criteria, $context)->first();
        if (empty($esd)) {
            return null;
        }

        if (empty($esd->getEsdMedia())) {
            return null;
        }

        return $esd->getEsdMedia();
    }

    public function getEsdOrderByCustomer($productId, SalesChannelContext $context): EsdOrderEntity
    {
        $criteria = $this->createCriteriaEsdOrder($context->getCustomer()->getId(), $productId);

        /** @var EsdOrderEntity $esdOrder */
        $esdOrder = $this->esdOrderRepository->search($criteria, $context->getContext())->first();

        return $esdOrder;
    }

    public function getEsdOrderListByCustomer(SalesChannelContext $context): EntitySearchResult
    {
        $criteria = $this->createCriteriaEsdOrder($context->getCustomer()->getId());

        return $this->esdOrderRepository->search($criteria, $context->getContext());
    }

    public function getEsdOrderByOrderLineItemIds(array $ids, Context $context): EsdOrderCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('orderLineItemId', $ids));

        /** @var EsdOrderCollection $esOrders */
        $esOrders = $this->esdOrderRepository->search($criteria, $context)->getEntities();

        return $esOrders;
    }

    private function createCriteriaEsdOrder(string $customerId, string $productId = null): Criteria
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

        $criteria->addFilter(
            new NotFilter(NotFilter::CONNECTION_AND, [
                new EqualsFilter('esd.esdMedia.mediaId', null),
            ])
        );

        $criteria->addSorting(new FieldSorting('createdAt', FieldSorting::DESCENDING));

        return $criteria;
    }

    public function getCompressFile($productId): string
    {
        return $this->getPrivateFolder() . $this->getPathCompressFile($productId);
    }

    public function getPathCompressFile($productId): string
    {
        return self::FOLDER_COMPRESS_NAME . "/$productId.zip";
    }

    public function downloadFileName($string): string
    {
        return $this->convertFileName($string) . '.zip';
    }

    private function convertFileName($string): string
    {
        $string = str_replace(' ', '-', $string);
        $string = preg_replace('/[^A-Za-z0-9\-]/', '', $string);
        return preg_replace('/-+/', '-', $string);
    }

    public function getPrivateFolder(): string
    {
        return dirname(__DIR__, 5) . '/files/';
    }

    private function checkExistAllFiles(EsdMediaCollection $esdMedia): bool
    {
        if (empty($esdMedia)) {
            return false;
        }

        foreach ($esdMedia as $media) {
            if (empty($media->getMedia())) {
                continue;
            }

            if (!is_file($this->urlGenerator->getRelativeMediaUrl($media->getMedia()))) {
                return false;
            }
        }

        return true;
    }
}
