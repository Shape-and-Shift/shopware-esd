<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class EsdMediaEntity extends Entity
{
    use EntityIdTrait;

    protected ?string $esdId = null;

    protected ?string $mediaId = null;

    protected ?MediaEntity $media = null;

    protected ?int $downloadLimitNumber = null;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getEsdId(): ?string
    {
        return $this->esdId;
    }

    public function setEsdId(string $esdId): void
    {
        $this->esdId = $esdId;
    }

    public function getMediaId(): ?string
    {
        return $this->mediaId;
    }

    public function setMediaId(string $mediaId): void
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

    public function getDownloadLimitNumber(): ?int
    {
        return $this->downloadLimitNumber;
    }

    public function setDownloadLimitNumber(int $downloadLimitNumber): void
    {
        $this->downloadLimitNumber = $downloadLimitNumber;
    }

    public function getApiAlias(): string
    {
        return 'esd_media';
    }
}
