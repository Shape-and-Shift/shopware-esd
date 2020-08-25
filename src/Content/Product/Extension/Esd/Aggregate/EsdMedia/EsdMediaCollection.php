<?php declare(strict_types=1);
namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(EsdMediaEntity $entity)
 * @method void              set(string $key, EsdMediaEntity $entity)
 * @method EsdMediaEntity[]    getIterator()
 * @method EsdMediaEntity[]    getElements()
 * @method EsdMediaEntity|null get(string $key)
 * @method EsdMediaEntity|null first()
 * @method EsdMediaEntity|null last()
 */
class EsdMediaCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return EsdMediaEntity::class;
    }
}
