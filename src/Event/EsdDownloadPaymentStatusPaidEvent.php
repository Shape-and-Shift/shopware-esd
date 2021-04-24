<?php declare(strict_types=1);

namespace Sas\Esd\Event;

use Shopware\Core\Checkout\Order\OrderDefinition;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\EventData\ArrayType;
use Shopware\Core\Framework\Event\EventData\EntityType;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;
use Shopware\Core\Framework\Event\EventData\ScalarValueType;
use Shopware\Core\Framework\Event\MailActionInterface;
use Shopware\Core\Framework\Event\SalesChannelAware;
use Symfony\Contracts\EventDispatcher\Event;

class EsdDownloadPaymentStatusPaidEvent extends Event implements MailActionInterface, SalesChannelAware
{
    public const EVENT_NAME = 'esd.download.payment.status.paid';

    /**
     * @var Context
     */
    private $context;

    /**
     * @var OrderEntity
     */
    private $order;

    /**
     * @var array
     */
    private $templateData;

    /**
     * @var MailRecipientStruct|null
     */
    private $mailRecipientStruct;

    public function __construct(
        Context $context,
        OrderEntity $order,
        array $templateData = []
    ) {
        $this->context = $context;
        $this->order = $order;
        $this->templateData = $templateData;
    }

    public function getOrder(): OrderEntity
    {
        return $this->order;
    }

    public function getEsdFiles(): array
    {
        if (empty($this->templateData['esdFiles'])) {
            return [];
        }

        return $this->templateData['esdFiles'];
    }

    public function getEsdOrderListIds(): array
    {
        if (empty($this->templateData['esdOrderListIds'])) {
            return [];
        }

        return $this->templateData['esdOrderListIds'];
    }

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
        if (!$this->mailRecipientStruct instanceof MailRecipientStruct) {
            $this->mailRecipientStruct = new MailRecipientStruct([
                $this->order->getOrderCustomer()->getEmail() => $this->order->getOrderCustomer()->getFirstName() . ' ' . $this->order->getOrderCustomer()->getLastName(),
            ]);
        }

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
}
