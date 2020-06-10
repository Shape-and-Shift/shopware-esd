<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia;

use Shopware\Core\Content\Media\MediaCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                    add(EsdMediaEntity $entity)
 * @method void                    set(string $key, EsdMediaEntity $entity)
 * @method EsdMediaEntity[]    getIterator()
 * @method EsdMediaEntity[]    getElements()
 * @method EsdMediaEntity|null get(string $key)
 * @method EsdMediaEntity|null first()
 * @method EsdMediaEntity|null last()
 */
class EsdMediaCollection extends EntityCollection
{
    public function getProductIds(): array
    {
        return $this->fmap(function (EsdMediaEntity $productMedia) {
            return $productMedia->getProductId();
        });
    }

    public function filterByProductId(string $id): self
    {
        return $this->filter(function (EsdMediaEntity $productMedia) use ($id) {
            return $productMedia->getProductId() === $id;
        });
    }

    public function getMediaIds(): array
    {
        return $this->fmap(function (EsdMediaEntity $productMedia) {
            return $productMedia->getMediaId();
        });
    }

    public function filterByMediaId(string $id): self
    {
        return $this->filter(function (EsdMediaEntity $productMedia) use ($id) {
            return $productMedia->getMediaId() === $id;
        });
    }

    public function getMedia(): MediaCollection
    {
        return new MediaCollection(
            $this->fmap(function (EsdMediaEntity $productMedia) {
                return $productMedia->getMedia();
            })
        );
    }

    protected function getExpectedClass(): string
    {
        return EsdMediaEntity::class;
    }
}
