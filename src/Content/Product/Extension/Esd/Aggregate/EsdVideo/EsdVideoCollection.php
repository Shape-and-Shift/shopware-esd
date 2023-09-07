<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdVideo;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<EsdVideoEntity>
 */
class EsdVideoCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return EsdVideoEntity::class;
    }
}
