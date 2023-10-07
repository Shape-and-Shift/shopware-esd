<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<EsdEntity>
 */
class EsdCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return EsdEntity::class;
    }
}
