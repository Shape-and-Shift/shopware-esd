<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<EsdMediaEntity>
 */
class EsdMediaCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return EsdMediaEntity::class;
    }
}
