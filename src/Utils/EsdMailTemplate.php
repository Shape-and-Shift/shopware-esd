<?php declare(strict_types=1);

namespace Sas\Esd\Utils;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Uuid\Uuid;

class EsdMailTemplate
{
    public const TEMPLATE_TYPE_DOWNLOAD_NAME = 'ESD - order download link';
    public const TEMPLATE_TYPE_DOWNLOAD_TECHNICAL_NAME = 'sas_esd.download';
    public const TEMPLATE_DOWNLOAD_SYSTEM_CONFIG_NAME = 'isSendDownloadConfirmation';

    public const TEMPLATE_TYPE_SERIAL_NAME = 'ESD - serial number';
    public const TEMPLATE_TYPE_SERIAL_TECHNICAL_NAME = 'sas_esd.serial';
    public const TEMPLATE_SERIAL_SYSTEM_CONFIG_NAME = 'isSendSerialConfirmation';

    public static function addDownloadMailTemplate(
        EntityRepositoryInterface $mailTemplateTypeRepository,
        EntityRepositoryInterface $mailTemplateRepository,
        Context $context
    ): void {
        $mailTemplateTypeId = Uuid::randomHex();
        $mailTemplateType = [
            [
                'id' => $mailTemplateTypeId,
                'name' => self::TEMPLATE_TYPE_DOWNLOAD_NAME,
                'technicalName' => self::TEMPLATE_TYPE_DOWNLOAD_TECHNICAL_NAME,
                'availableEntities' => [
                    'order' => 'order',
                    'salesChannel' => 'sales_channel',
                ],
            ],
        ];

        $mailTemplate = [
            [
                'id' => Uuid::randomHex(),
                'mailTemplateTypeId' => $mailTemplateTypeId,
                'senderName' => [
                    'en-GB' => 'No Reply',
                    'de-DE' => 'No Reply',
                ],
                'subject' => [
                    'en-GB' => 'Your download product of order {{ order.orderNumber }}',
                    'de-DE' => 'Ihr Download-Produkt der Bestellung {{ order.orderNumber }}',
                ],
                'description' => [
                    'en-GB' => 'Download link template',
                    'de-DE' => 'Linkvorlage herunterladen',
                ],
                'contentPlain' => [
                    'en-GB' => self::getDownloadPlainMailTemplate(),
                    'de-DE' => self::getDownloadPlainMailTemplateGerman(),
                ],
                'contentHtml' => [
                    'en-GB' => self::getDownloadHtmlMailTemplate(),
                    'de-DE' => self::getDownloadHtmlMailTemplateInGerman(),
                ],
            ],
        ];

        try {
            $mailTemplateTypeRepository->create($mailTemplateType, $context);
            $mailTemplateRepository->create($mailTemplate, $context);
        } catch (UniqueConstraintViolationException $exception) {
        }
    }

    public static function addSerialMailTemplate(
        EntityRepositoryInterface $mailTemplateTypeRepository,
        EntityRepositoryInterface $mailTemplateRepository,
        Context $context
    ): void {
        $mailTemplateTypeId = Uuid::randomHex();
        $mailTemplateType = [
            [
                'id' => $mailTemplateTypeId,
                'name' => self::TEMPLATE_TYPE_SERIAL_NAME,
                'technicalName' => self::TEMPLATE_TYPE_SERIAL_TECHNICAL_NAME,
                'availableEntities' => [
                    'order' => 'order',
                    'salesChannel' => 'sales_channel',
                ],
            ],
        ];

        $mailTemplate = [
            [
                'id' => Uuid::randomHex(),
                'mailTemplateTypeId' => $mailTemplateTypeId,
                'senderName' => [
                    'en-GB' => 'No Reply',
                    'de-DE' => 'No Reply',
                ],
                'subject' => [
                    'en-GB' => 'Your serial number from the product of order {{ order.orderNumber }}',
                    'de-DE' => 'Ihre Seriennummer aus dem Produkt der Bestellung {{ order.orderNumber }}',
                ],
                'description' => [
                    'en-GB' => 'Serial number template',
                    'de-DE' => 'Seriennummernvorlage',
                ],
                'contentPlain' => [
                    'en-GB' => self::getSerialPlainMailTemplate(),
                    'de-DE' => self::getSerialPlainMailTemplateGerman(),
                ],
                'contentHtml' => [
                    'en-GB' => self::getSerialHtmlMailTemplate(),
                    'de-DE' => self::getSerialHtmlMailTemplateInGerman(),
                ],
            ],
        ];

        try {
            $mailTemplateTypeRepository->create($mailTemplateType, $context);
            $mailTemplateRepository->create($mailTemplate, $context);
        } catch (UniqueConstraintViolationException $exception) {
        }
    }

    public static function removeMailTemplate(
        EntityRepositoryInterface $mailTemplateTypeRepository,
        EntityRepositoryInterface $mailTemplateRepository,
        Context $context
    ): void {
        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsAnyFilter('technicalName', [
                self::TEMPLATE_TYPE_DOWNLOAD_TECHNICAL_NAME,
                self::TEMPLATE_TYPE_SERIAL_TECHNICAL_NAME,
            ])
        );

        /** @var MailTemplateTypeCollection $myCustomMailTemplateTypes */
        $myCustomMailTemplateTypes = $mailTemplateTypeRepository->search($criteria, $context)->getEntities();
        if (empty($myCustomMailTemplateTypes)) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsAnyFilter('mailTemplateTypeId', array_values($myCustomMailTemplateTypes->getIds()))
        );

        $mailTemplateIds = $mailTemplateRepository->searchIds($criteria, $context)->getIds();
        if (!empty($mailTemplateIds)) {
            $ids = array_map(static function ($id) {
                return ['id' => $id];
            }, $mailTemplateIds);
            $mailTemplateRepository->delete($ids, $context);
        }

        $myCustomMailTemplateTypeIds = [];
        foreach ($myCustomMailTemplateTypes->getIds() as $myCustomMailTemplateTypeId) {
            $myCustomMailTemplateTypeIds[] = [
                'id' => $myCustomMailTemplateTypeId,
            ];
        }

        if (!empty($myCustomMailTemplateTypeIds)) {
            $mailTemplateTypeRepository->delete($myCustomMailTemplateTypeIds, $context);
        }
    }

    private static function getDownloadHtmlMailTemplate(): string
    {
        return file_get_contents(__DIR__ . '/../Resources/views/mail-template/download-html-mail-template.html.twig');
    }

    private static function getDownloadPlainMailTemplate(): string
    {
        return file_get_contents(__DIR__ . '/../Resources/views/mail-template/download-plain-mail-template.html.twig');
    }

    private static function getDownloadHtmlMailTemplateInGerman(): string
    {
        return file_get_contents(__DIR__ . '/../Resources/views/mail-template/de/download-html-mail-template.html.twig');
    }

    private static function getDownloadPlainMailTemplateGerman(): string
    {
        return file_get_contents(__DIR__ . '/../Resources/views/mail-template/de/download-plain-mail-template.html.twig');
    }

    private static function getSerialHtmlMailTemplate(): string
    {
        return file_get_contents(__DIR__ . '/../Resources/views/mail-template/serial-html-mail-template.html.twig');
    }

    private static function getSerialPlainMailTemplate(): string
    {
        return file_get_contents(__DIR__ . '/../Resources/views/mail-template/serial-plain-mail-template.html.twig');
    }

    private static function getSerialHtmlMailTemplateInGerman(): string
    {
        return file_get_contents(__DIR__ . '/../Resources/views/mail-template/de/serial-html-mail-template.html.twig');
    }

    private static function getSerialPlainMailTemplateGerman(): string
    {
        return file_get_contents(__DIR__ . '/../Resources/views/mail-template/de/serial-plain-mail-template.html.twig');
    }
}
