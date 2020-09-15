<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdDownloadHistory;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                          add(EsdDownloadHistoryEntity $entity)
 * @method void                          set(string $key, EsdDownloadHistoryEntity $entity)
 * @method EsdDownloadHistoryEntity[]    getIterator()
 * @method EsdDownloadHistoryEntity[]    getElements()
 * @method EsdDownloadHistoryEntity|null get(string $key)
 * @method EsdDownloadHistoryEntity|null first()
 * @method EsdDownloadHistoryEntity|null last()
 */
class EsdDownloadHistoryCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return EsdDownloadHistoryEntity::class;
    }
}
