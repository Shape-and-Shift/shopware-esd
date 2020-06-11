<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder\EsdOrderEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class EsdSerialEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $esdId;

    /**
     * @var EsdEntity
     */
    protected $esd;

    /**
     * @var string
     */
    protected $serial;

    /**
     * @var EsdOrderEntity|null
     */
    protected $esdOrder;

    public function getEsdId(): string
    {
        return $this->esdId;
    }

    public function setEsdId(string $esdId): void
    {
        $this->esdId = $esdId;
    }

    public function getEsd(): EsdEntity
    {
        return $this->esd;
    }

    public function setEsd(EsdEntity $esd): void
    {
        $this->esd = $esd;
    }

    public function getSerial(): string
    {
        return $this->serial;
    }

    public function setSerial(string $serial): void
    {
        $this->serial = $serial;
    }

    public function getEsdOrder(): ?EsdOrderEntity
    {
        return $this->esdOrder;
    }

    public function setEsdOrder(?EsdOrderEntity $esdOrder): void
    {
        $this->esdOrder = $esdOrder;
    }
}
