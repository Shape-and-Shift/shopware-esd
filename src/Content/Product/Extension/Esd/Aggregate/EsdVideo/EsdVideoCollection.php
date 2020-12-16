<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdVideo;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                add(EsdVideoEntity $entity)
 * @method void                set(string $key, EsdVideoEntity $entity)
 * @method EsdVideoEntity[]    getIterator()
 * @method EsdVideoEntity[]    getElements()
 * @method EsdVideoEntity|null get(string $key)
 * @method EsdVideoEntity|null first()
 * @method EsdVideoEntity|null last()
 */
class EsdVideoCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return EsdVideoEntity::class;
    }
}
