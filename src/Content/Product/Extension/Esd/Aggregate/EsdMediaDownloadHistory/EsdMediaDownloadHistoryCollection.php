<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMediaDownloadHistory;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<EsdMediaDownloadHistoryEntity>
 */
class EsdMediaDownloadHistoryCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return EsdMediaDownloadHistoryEntity::class;
    }
}
