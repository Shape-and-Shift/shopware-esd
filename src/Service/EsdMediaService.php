<?php declare(strict_types=1);

namespace Sas\Esd\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Api\Context\AdminApiSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;

class EsdMediaService
{
    public function __construct(
        protected readonly LoggerInterface $logger,
        protected readonly EntityRepository $mediaRepository
    ) {
    }

    public function getAdminSystemMedia(string $fileName, string $fileExtension, Context $context): ?MediaEntity
    {
        $contextSource = $context->getSource();
        if (!$contextSource instanceof AdminApiSource) {
            $this->logger->critical(
                sprintf('Cannot view media with file name: %s Context source is not AdminApiSource', $fileName)
            );

            return null;
        }

        return $this->getMedia($fileName, $fileExtension, $context);
    }

    public function getAdminSystemMediaById(string $mediaId, Context $context): ?MediaEntity
    {
        $contextSource = $context->getSource();
        if (!$contextSource instanceof AdminApiSource) {
            $this->logger->critical(
                sprintf('Cannot view media with ID: %s Context source is not AdminApiSource', $mediaId)
            );

            return null;
        }

        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(
            new EqualsFilter('id', $mediaId),
        );

        $media = $context->scope(Context::SYSTEM_SCOPE, function ($context) use ($criteria) {
            return $this->mediaRepository->search($criteria, $context)->first();
        });

        if (!$media instanceof MediaEntity) {
            return null;
        }

        return $media;
    }

    private function getMedia(string $fileName, string $fileExtension, Context $context): ?MediaEntity
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->addFilter(
            new MultiFilter('AND', [
                new EqualsFilter('fileName', $fileName),
                new EqualsFilter('fileExtension', $fileExtension),
            ])
        );

        $media = $context->scope(Context::SYSTEM_SCOPE, function ($context) use ($criteria) {
            return $this->mediaRepository->search($criteria, $context)->first();
        });

        if (!$media instanceof MediaEntity) {
            $this->logger->critical(
                sprintf('Could not fetch media with file name %s', $fileName)
            );

            return null;
        }

        return $media;
    }
}
