<?php declare(strict_types=1);
namespace Sas\Esd\Content\Product\Extension\Esd;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaCollection;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class EsdEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $productId;

    /**
     * @var ProductEntity|null
     */
    protected $product;

    /**
     * @var bool
     */
    protected $hasSerial;

    /**
     * @var string|null
     */
    protected $mediaId;

    /**
     * @var MediaEntity|null
     */
    protected $media;

    /**
     * @var EsdMediaCollection|null
     */
    protected $esdMedia;

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getProductId(): string
    {
        return $this->productId;
    }

    /**
     * @param string $productId
     */
    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    /**
     * @return ProductEntity|null
     */
    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    /**
     * @param ProductEntity|null $product
     */
    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
    }

    /**
     * @return bool
     */
    public function hasSerial(): bool
    {
        return $this->hasSerial;
    }

    /**
     * @param bool $hasSerial
     */
    public function setHasSerial(bool $hasSerial): void
    {
        $this->hasSerial = $hasSerial;
    }

    public function getMediaId(): ?string
    {
        return $this->mediaId;
    }

    public function setMediaId(?string $mediaId): void
    {
        $this->mediaId = $mediaId;
    }

    public function getMedia(): ?MediaEntity
    {
        return $this->media;
    }

    public function setMedia(?MediaEntity $media): void
    {
        $this->media = $media;
    }

    public function getEsdMedia(): ?EsdMediaCollection
    {
        return $this->esdMedia;
    }

    public function setEsdMedia(?EsdMediaCollection $esdMedia): void
    {
        $this->esdMedia = $esdMedia;
    }

    public function getApiAlias(): string
    {
        return 'esd';
    }
}
