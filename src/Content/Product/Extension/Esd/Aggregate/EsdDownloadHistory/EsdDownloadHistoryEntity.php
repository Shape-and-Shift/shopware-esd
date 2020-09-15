<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdDownloadHistory;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class EsdDownloadHistoryEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string|null
     */
    protected $esdOrderId;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function getEsdOrderId(): string
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
