<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @extends EntityCollection<EsdSerialEntity>
 */
class EsdSerialCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return EsdSerialEntity::class;
    }
}
