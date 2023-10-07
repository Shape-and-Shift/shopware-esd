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

    protected string $esdId;

    protected ?EsdEntity $esd = null;

    protected string $orderLineItemId;

    protected ?string $serialId = null;

    protected ?EsdSerialEntity $serial = null;

    protected ?int $countDownload = null;

    protected ?OrderLineItemEntity $orderLineItem = null;

    public function getEsdId(): string
    {
        return $this->esdId;
    }

    public function setEsdId(string $esdId): void
    {
        $this->esdId = $esdId;
    }

    public function getEsd(): ?EsdEntity
    {
        return $this->esd;
    }

    public function setEsd(?EsdEntity $esd): void
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

    public function getOrderLineItem(): ?OrderLineItemEntity
    {
        return $this->orderLineItem;
    }

    public function setOrderLineItem(?OrderLineItemEntity $orderLineItem): void
    {
        $this->orderLineItem = $orderLineItem;
    }

    public function getSerialId(): ?string
    {
        return $this->serialId;
    }

    public function setSerialId(?string $serialId): void
    {
        $this->serialId = $serialId;
    }

    public function getSerial(): ?EsdSerialEntity
    {
        return $this->serial;
    }

    public function setSerial(?EsdSerialEntity $serial): void
    {
        $this->serial = $serial;
    }

    public function getCountDownload(): ?int
    {
        return $this->countDownload;
    }

    public function setCountDownload(int $countDownload): void
    {
        $this->countDownload = $countDownload;
    }
}
