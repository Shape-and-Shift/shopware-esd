<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(EsdSerialEntity $entity)
 * @method void              set(string $key, EsdSerialEntity $entity)
 * @method EsdSerialEntity[]    getIterator()
 * @method EsdSerialEntity[]    getElements()
 * @method EsdSerialEntity|null get(string $key)
 * @method EsdSerialEntity|null first()
 * @method EsdSerialEntity|null last()
 */
class EsdSerialCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return EsdSerialEntity::class;
    }
}
