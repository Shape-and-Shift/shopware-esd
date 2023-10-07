<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<EsdOrderEntity>
 */
class EsdOrderCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return EsdOrderEntity::class;
    }
}
