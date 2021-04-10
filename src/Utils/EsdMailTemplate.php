<?php declare(strict_types=1);

namespace Sas\Esd\Utils;

use Shopware\Core\Content\MailTemplate\Aggregate\MailTemplateType\MailTemplateTypeCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;

class EsdMailTemplate
{
    public const TEMPLATE_TYPE_DOWNLOAD_NAME = 'ESD - order download link';
    public const TEMPLATE_TYPE_DOWNLOAD_NAME_DE = 'ESD - Download-Link bestellen';
    public const TEMPLATE_TYPE_DOWNLOAD_TECHNICAL_NAME = 'sas_esd.download';
    public const TEMPLATE_DOWNLOAD_SYSTEM_CONFIG_NAME = 'isSendDownloadConfirmation';

    public const TEMPLATE_TYPE_SERIAL_NAME = 'ESD - serial number';
    public const TEMPLATE_TYPE_SERIAL_NAME_DE = 'ESD - Ordnungsnummer';
    public const TEMPLATE_TYPE_SERIAL_TECHNICAL_NAME = 'sas_esd.serial';
    public const TEMPLATE_SERIAL_SYSTEM_CONFIG_NAME = 'isSendSerialConfirmation';

    public const TEMPLATE_TYPE_DOWNLOAD_DISABLED_ZIP_NAME = 'ESD - order download link with disabled zip';
    public const TEMPLATE_TYPE_DOWNLOAD_DISABLED_ZIP_NAME_DE = 'ESD - order download link with disabled zip';
    public const TEMPLATE_TYPE_DOWNLOAD_DISABLED_ZIP_TECHNICAL_NAME = 'sas_esd.download.disabled.zip';
    public const TEMPLATE_DOWNLOAD_DISABLED_ZIP_SYSTEM_CONFIG_NAME = 'isDisableZipFile';

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

    public static function getDownloadHtmlMailTemplate(): string
    {
        return file_get_contents(__DIR__ . '/../Resources/views/mail-template/download-html-mail-template.html.twig');
    }

    public static function getDownloadPlainMailTemplate(): string
    {
        return file_get_contents(__DIR__ . '/../Resources/views/mail-template/download-plain-mail-template.html.twig');
    }

    public static function getDownloadHtmlMailTemplateInGerman(): string
    {
        return file_get_contents(__DIR__ . '/../Resources/views/mail-template/de/download-html-mail-template.html.twig');
    }

    public static function getDownloadPlainMailTemplateGerman(): string
    {
        return file_get_contents(__DIR__ . '/../Resources/views/mail-template/de/download-plain-mail-template.html.twig');
    }

    public static function getDownloadHtmlMailTemplateForDisabledZip(): string
    {
        return file_get_contents(
            __DIR__ . '/../Resources/views/mail-template/download-disabled-zip-html-mail-template.html.twig'
        );
    }

    public static function getDownloadPlainMailTemplateForDisabledZip(): string
    {
        return file_get_contents(
            __DIR__ . '/../Resources/views/mail-template/download-disabled-zip-plain-mail-template.html.twig'
        );
    }

    public static function getDownloadHtmlMailTemplateForDisabledZipInGerman(): string
    {
        return file_get_contents(
            __DIR__ . '/../Resources/views/mail-template/de/download-disabled-zip-html-mail-template.html.twig'
        );
    }

    public static function getDownloadPlainMailTemplateForDisabledZipInGerman(): string
    {
        return file_get_contents(
            __DIR__ . '/../Resources/views/mail-template/de/download-disabled-zip-plain-mail-template.html.twig'
        );
    }

    public static function getSerialHtmlMailTemplate(): string
    {
        return file_get_contents(__DIR__ . '/../Resources/views/mail-template/serial-html-mail-template.html.twig');
    }

    public static function getSerialPlainMailTemplate(): string
    {
        return file_get_contents(__DIR__ . '/../Resources/views/mail-template/serial-plain-mail-template.html.twig');
    }

    public static function getSerialHtmlMailTemplateInGerman(): string
    {
        return file_get_contents(__DIR__ . '/../Resources/views/mail-template/de/serial-html-mail-template.html.twig');
    }

    public static function getSerialPlainMailTemplateGerman(): string
    {
        return file_get_contents(__DIR__ . '/../Resources/views/mail-template/de/serial-plain-mail-template.html.twig');
    }
}
