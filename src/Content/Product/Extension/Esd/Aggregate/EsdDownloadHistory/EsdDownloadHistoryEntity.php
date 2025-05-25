<?php
declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdDownloadHistory;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class EsdDownloadHistoryEntity extends Entity
{
    use EntityIdTrait;

    protected ?string $esdOrderId = null;

    protected ?EsdOrderEntity $esdOrder = null;

    public function getEsdOrder(): ?EsdOrderEntity
    {
        return $this->esdOrder;
    }

    public function setEsdOrder(?EsdOrderEntity $esdOrder): void
    {
        $this->esdOrder = $esdOrder;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getEsdOrderId(): ?string
    {
        return $this->esdOrderId;
    }

    public function setEsdOrderId(string $esdOrderId): void
    {
        $this->esdOrderId = $esdOrderId;
    }

    public function getApiAlias(): string
    {
        return 'esd_download_history';
    }
}
