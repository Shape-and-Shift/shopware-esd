<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMediaDownloadHistory;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                               add(EsdMediaDownloadHistoryEntity $entity)
 * @method void                               set(string $key, EsdMediaDownloadHistoryEntity $entity)
 * @method EsdMediaDownloadHistoryEntity[]    getIterator()
 * @method EsdMediaDownloadHistoryEntity[]    getElements()
 * @method EsdMediaDownloadHistoryEntity|null get(string $key)
 * @method EsdMediaDownloadHistoryEntity|null first()
 * @method EsdMediaDownloadHistoryEntity|null last()
 */
class EsdMediaDownloadHistoryCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return EsdMediaDownloadHistoryEntity::class;
    }
}
