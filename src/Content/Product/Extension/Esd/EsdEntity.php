<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaCollection;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial\EsdSerialCollection;
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
     * @var bool|null
     */
    protected $hasCustomDownloadLimit;

    /**
     * @var bool|null
     */
    protected $hasUnlimitedDownload;

    /**
     * @var int|null
     */
    protected $downloadLimitNumber;

    /**
     * @var EsdSerialCollection|null
     */
    protected $serial;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
    }

    public function hasSerial(): bool
    {
        return $this->hasSerial;
    }

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

    public function getHasCustomDownloadLimit(): ?bool
    {
        return $this->hasCustomDownloadLimit;
    }

    public function setHasCustomDownloadLimit($hasCustomDownloadLimit): void
    {
        $this->hasCustomDownloadLimit = $hasCustomDownloadLimit;
    }

    public function getDownloadLimitNumber(): ?int
    {
        return $this->downloadLimitNumber;
    }

    public function setDownloadLimitNumber($downloadLimitNumber): void
    {
        $this->downloadLimitNumber = $downloadLimitNumber;
    }

    public function getHasUnlimitedDownload(): ?bool
    {
        return $this->hasUnlimitedDownload;
    }

    public function setHasUnlimitedDownload($hasUnlimitedDownload): void
    {
        $this->hasUnlimitedDownload = $hasUnlimitedDownload;
    }

    public function getSerial(): ?EsdSerialCollection
    {
        return $this->serial;
    }

    public function setSerial(?EsdSerialCollection $serial): void
    {
        $this->serial = $serial;
    }

    public function getApiAlias(): string
    {
        return 'esd';
    }
}
