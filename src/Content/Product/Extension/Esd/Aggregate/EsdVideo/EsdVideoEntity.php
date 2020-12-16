<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdVideo;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class EsdVideoEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $esdMediaId;

    /**
     * @var int
     */
    protected $option;

    public function getEsdMediaId(): string
    {
        return $this->esdMediaId;
    }

    public function setEsdMediaId(string $esdMediaId): void
    {
        $this->esdMediaId = $esdMediaId;
    }

    public function getOption(): int
    {
        return $this->option;
    }

    public function setOption(int $option): void
    {
        $this->option = $option;
    }

    public function getApiAlias(): string
    {
        return 'sas_product_esd_video';
    }
}
