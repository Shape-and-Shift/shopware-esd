<?php declare(strict_types=1);

namespace Sas\Esd\Event;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Flow\Dispatching\Aware\ScalarValuesAware;
use Shopware\Core\Content\Flow\Exception\CustomerDeletedException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\AssociationNotFoundException;
use Shopware\Core\Framework\Event\CustomerAware;
use Shopware\Core\Framework\Event\EventData\ArrayType;
use Shopware\Core\Framework\Event\EventData\EntityType;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;
use Shopware\Core\Framework\Event\EventData\ScalarValueType;
use Shopware\Core\Framework\Event\MailAware;
use Shopware\Core\Framework\Event\OrderAware;
use Shopware\Core\Framework\Event\SalesChannelAware;
use Symfony\Contracts\EventDispatcher\Event;

class EsdSerialPaymentStatusPaidEvent extends Event implements ScalarValuesAware, SalesChannelAware, OrderAware, MailAware, CustomerAware
{
    public const EVENT_NAME = 'esd.serial.payment.status.paid';

    private ?MailRecipientStruct $mailRecipientStruct;

    public function __construct(
        private readonly Context $context,
        private readonly OrderEntity $order,
        private readonly array $templateData = []
    ) {
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function getEsdSerials(): array
    {
        if (empty($this->templateData['esdSerials'])) {
            return [];
        }

        $esdSerials = $this->templateData['esdSerials'];
        usort($esdSerials, function ($a, $b) {
            return $a['productName'] <=> $b['productName'];
        });

        return $esdSerials;
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('order', new EntityType(OrderDefinition::class))
            ->add('esdSerials', new ArrayType(new ScalarValueType(ScalarValueType::TYPE_STRING)));
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getMailStruct(): MailRecipientStruct
    {
        $orderCustomer = $this->order->getOrderCustomer();
        if ($orderCustomer === null) {
            throw new AssociationNotFoundException('orderCustomer');
        }

        $this->mailRecipientStruct = new MailRecipientStruct([
            $orderCustomer->getEmail() => $orderCustomer->getFirstName() . ' ' . $orderCustomer->getLastName(),
        ]);

        return $this->mailRecipientStruct;
    }

    public function getSalesChannelId(): string
    {
        return $this->order->getSalesChannelId();
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @throws CustomerDeletedException
     */
    public function getCustomerId(): string
    {
        $customer = $this->getOrder()->getOrderCustomer();

        if ($customer === null || $customer->getCustomerId() === null) {
            throw new CustomerDeletedException($this->getOrderId());
        }

        return $customer->getCustomerId();
    }

    public function getOrderId(): string
    {
        return $this->order->getId();
    }

    public function getValues(): array
    {
        return [
            'esdData' => $this->templateData,
        ];
    }
}
