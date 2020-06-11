<?php declare(strict_types=1);

namespace Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdOrder;

use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdSerial\EsdSerialEntity;
use Sas\Esd\Content\Product\Extension\Esd\EsdEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class EsdOrderEntity extends Entity
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
    protected $orderLineItemId;

    /**
     * @var string|null
     */
    protected $serialId;

    /**
     * @var EsdSerialEntity
     */
    protected $serial;

    /**
     * @var OrderLineItemEntity
     */
    protected $orderLineItem;

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

    public function getOrderLineItemId(): string
    {
        return $this->orderLineItemId;
    }

    public function setOrderLineItemId(string $orderLineItemId): void
    {
        $this->orderLineItemId = $orderLineItemId;
    }

    public function getOrderLineItem(): OrderLineItemEntity
    {
        return $this->orderLineItem;
    }

    public function setOrderLineItem(OrderLineItemEntity $orderLineItem): void
    {
        $this->orderLineItem = $orderLineItem;
    }

    /**
     * @return string|null
     */
    public function getSerialId(): ?string
    {
        return $this->serialId;
    }

    /**
     * @param string|null $serialId
     */
    public function setSerialId(?string $serialId): void
    {
        $this->serialId = $serialId;
    }

    /**
     * @return EsdSerialEntity|null
     */
    public function getSerial(): ?EsdSerialEntity
    {
        return $this->serial;
    }

    /**
     * @param EsdSerialEntity $serial
     */
    public function setSerial(EsdSerialEntity $serial): void
    {
        $this->serial = $serial;
    }
}
