<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void              add(EsdOrderEntity $entity)
 * @method void              set(string $key, EsdOrderEntity $entity)
 * @method EsdOrderEntity[]    getIterator()
 * @method EsdOrderEntity[]    getElements()
 * @method EsdOrderEntity|null get(string $key)
 * @method EsdOrderEntity|null first()
 * @method EsdOrderEntity|null last()
 */
class EsdOrderCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return EsdOrderEntity::class;
    }
}
