<?php
declare(strict_types=1);

namespace Sas\Esd\Event;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Flow\Dispatching\Aware\ScalarValuesAware;
use Shopware\Core\Content\Flow\Exception\CustomerDeletedException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\CustomerAware;
use Shopware\Core\Framework\Event\EventData\ArrayType;
use Shopware\Core\Framework\Event\EventData\EntityType;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;
use Shopware\Core\Framework\Event\EventData\ScalarValueType;
use Shopware\Core\Framework\Event\FlowEventAware;
use Shopware\Core\Framework\Event\MailAware;
use Shopware\Core\Framework\Event\OrderAware;
use Shopware\Core\Framework\Event\SalesChannelAware;
use Shopware\Core\Framework\FrameworkException;
use Symfony\Contracts\EventDispatcher\Event;

class EsdDownloadPaymentStatusPaidEvent extends Event implements ScalarValuesAware, SalesChannelAware, OrderAware, MailAware, CustomerAware, FlowEventAware
{
    public const EVENT_NAME = 'esd.download.payment.status.paid';

    private string $salesChannelId;

    /**
     * @param array<string, mixed> $templateData
     */
    public function __construct(
        private readonly Context $context,
        private readonly OrderEntity $order,
        private readonly array $templateData = []
    ) {
        $this->salesChannelId = $order->getSalesChannelId();
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEsdFiles(): array
    {
        if (empty($this->templateData['esdFiles'])) {
            return [];
        }

        return $this->templateData['esdFiles'];
    }

    /**
     * @return array<string>
     */
    public function getEsdOrderListIds(): array
    {
        if (empty($this->templateData['esdOrderListIds'])) {
            return [];
        }

        return $this->templateData['esdOrderListIds'];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEsdMediaFiles(): array
    {
        if (empty($this->templateData['esdMediaFiles'])) {
            return [];
        }

        return $this->templateData['esdMediaFiles'];
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('order', new EntityType(OrderDefinition::class))
            ->add('esdFiles', new ArrayType(new ScalarValueType(ScalarValueType::TYPE_STRING)))
            ->add('esdOrderListIds', new ArrayType(new ScalarValueType(ScalarValueType::TYPE_STRING)))
            ->add('esdMediaFiles', new ArrayType(new ScalarValueType(ScalarValueType::TYPE_STRING)));
    }

    public function getName(): string
    {
        return self::EVENT_NAME;
    }

    public function getMailStruct(): MailRecipientStruct
    {
        $orderCustomer = $this->order->getOrderCustomer();
        if ($orderCustomer === null) {
            // @phpstan-ignore-next-line
            throw FrameworkException::associationNotFound('orderCustomer');
        }

        return new MailRecipientStruct([
            $orderCustomer->getEmail() => $orderCustomer->getFirstName() . ' ' . $orderCustomer->getLastName(),
        ]);
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
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
            ...$this->templateData,
        ];
    }
}
