<?php declare(strict_types=1);
namespace Sas\Esd\Content\Product\Extension\Esd;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(EsdEntity $entity)
 * @method void              set(string $key, EsdEntity $entity)
 * @method EsdEntity[]    getIterator()
 * @method EsdEntity[]    getElements()
 * @method EsdEntity|null get(string $key)
 * @method EsdEntity|null first()
 * @method EsdEntity|null last()
 */
class EsdCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return EsdEntity::class;
    }
}
