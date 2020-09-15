<?php declare(strict_types=1);

namespace Sas\Esd\Service;

use Sas\Esd\Message\SendMailMessage;
use Sas\Esd\Utils\EsdMailTemplate;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\Messenger\MessageBusInterface;

class EsdMailService
{
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var EntityRepositoryInterface
     */
    private $mailTemplateRepository;

    /**
     * @var EsdService
     */
    private $esdService;

    /**
     * @var MessageBusInterface
     */
    private $messageBus;

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $mailTemplateRepository,
        EsdService $esdService,
        MessageBusInterface $messageBus
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->esdService = $esdService;
        $this->messageBus = $messageBus;
    }

    public function sendMailDownload(
        OrderEntity $order,
        array $esdOrderLineItems,
        array $esdOrderListIds,
        Context $context
    ): void {
        if (empty($esdOrderLineItems)) {
            return;
        }

        $templateData['order'] = $order;
        $templateData['esdFiles'] = [];
        $templateData['esdOrderListIds'] = $esdOrderListIds;
        /** @var OrderLineItemEntity $lineItem */
        foreach ($esdOrderLineItems as $lineItem) {
            $templateData['esdFiles'][$lineItem->getProductId()] = $this->esdService->getFileSize($lineItem->getProductId());
        }

        $this->sendMail(
            $order,
            $context,
            EsdMailTemplate::TEMPLATE_DOWNLOAD_SYSTEM_CONFIG_NAME,
            EsdMailTemplate::TEMPLATE_TYPE_DOWNLOAD_TECHNICAL_NAME,
            $templateData
        );
    }

    public function sendMailSerial(
        OrderEntity $order,
        array $esdSerials,
        Context $context
    ): void {
        if (empty($esdSerials)) {
            return;
        }

        $templateData['order'] = $order;
        $templateData['esdSerials'] = $esdSerials;

        $this->sendMail(
            $order,
            $context,
            EsdMailTemplate::TEMPLATE_SERIAL_SYSTEM_CONFIG_NAME,
            EsdMailTemplate::TEMPLATE_TYPE_SERIAL_TECHNICAL_NAME,
            $templateData
        );
    }

    public function sendMail(
        OrderEntity $order,
        Context $context,
        string $systemConfigName,
        string $technicalName,
        array $templateData = []
    ): void {
        if (!$this->getSystemConfig($systemConfigName)) {
            return;
        }

        $mailTemplate = $this->getMailTemplate($context, $technicalName, $order);
        if (empty($mailTemplate)) {
            return;
        }

        $data = new DataBag();
        $data->set('salesChannelId', $order->getSalesChannelId());
        $data->set('subject', $mailTemplate->getSubject());
        $data->set('senderName', $mailTemplate->getSenderName());

        $customerName = $order->getOrderCustomer()->getFirstName() . ' ' . $order->getOrderCustomer()->getLastName();
        $data->set('recipients', [$order->getOrderCustomer()->getEmail() => $customerName]);
        $data->set('contentHtml', $mailTemplate->getContentHtml());
        $data->set('contentPlain', $mailTemplate->getContentPlain());

        $mail['data'] = $data->all();
        $mail['context'] = $context;
        $mail['templateData'] = $templateData;

        $message = new SendMailMessage();
        $message->setMail($mail);

        $this->messageBus->dispatch($message);
    }

    private function getMailTemplate(Context $context, string $technicalName, OrderEntity $order): ?MailTemplateEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation('salesChannels');
        $criteria->addFilter(new EqualsFilter('mailTemplateType.technicalName', $technicalName));
        $criteria->addFilter(new EqualsFilter('salesChannels.salesChannelId', $order->getSalesChannelId()));
        $criteria->setLimit(1);

        return $this->mailTemplateRepository->search($criteria, $context)->first();
    }

    private function getSystemConfig(string $name): bool
    {
        $isSendDownloadConfirmation = $this->systemConfigService->get('SasEsd.config.' . $name);
        if (empty($isSendDownloadConfirmation)) {
            return false;
        }

        return true;
    }
}
