<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdDownloadHistory;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<EsdDownloadHistoryEntity>
 */
class EsdDownloadHistoryCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return EsdDownloadHistoryEntity::class;
    }
}
