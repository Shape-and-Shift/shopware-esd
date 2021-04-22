<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMediaDownloadHistory;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class EsdMediaDownloadHistoryEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string|null
     */
    protected $esdOrderId;

    /**
     * @var string|null
     */
    protected $esdMediaId;

    public function getEsdOrderId(): string
    {
        return $this->esdOrderId;
    }

    public function setEsdOrderId(string $esdOrderId): void
    {
        $this->esdOrderId = $esdOrderId;
    }

    public function getEsdMediaId(): string
    {
        return $this->esdMediaId;
    }

    public function setEsdMediaId(string $esdMediaId): void
    {
        $this->esdMediaId = $esdMediaId;
    }

    public function getApiAlias(): string
    {
        return 'esd_media_download_history';
    }
}
