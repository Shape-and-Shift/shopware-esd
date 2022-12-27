<?php declare(strict_types=1);

namespace Sas\Esd\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Sas\Esd\Event\EsdDownloadPaymentStatusPaidDisabledZipEvent;
use Sas\Esd\Utils\EsdMailTemplate;
use Shopware\Core\Content\MailTemplate\MailTemplateActions;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1617902528CreateDownloadMailEventActionForDisabledZip extends MigrationStep
{
    private const GERMAN_LANGUAGE_NAME = 'Deutsch';
    private const ENGLISH_LANGUAGE_NAME = 'English';

    public function getCreationTimestamp(): int
    {
        return 1617902528;
    }

    public function update(Connection $connection): void
    {
        $this->insertEventAction($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function insertEventAction(Connection $connection): void
    {
        $templateId = null;
        $templateTypeId = null;
        $fetchTemplateTypeId = $this->fetchTemplateTypeId(
            EsdMailTemplate::TEMPLATE_TYPE_DOWNLOAD_DISABLED_ZIP_TECHNICAL_NAME,
            $connection
        );
        if ($fetchTemplateTypeId) {
            $templateTypeId = $fetchTemplateTypeId;
            $templateId = $this->fetchTemplateId($templateTypeId, $connection);
        } else {
            $templateTypeId = Uuid::randomBytes();
            $templateId = Uuid::randomBytes();
            $this->insertMailTemplateType($templateTypeId, $connection);
            $this->insertMailTemplate($templateId, $templateTypeId, $connection);
        }

        if ($templateId && $templateTypeId) {
            $connection->insert(
                'event_action',
                [
                    'id' => Uuid::randomBytes(),
                    'title' => 'ESD - Download mail with disabled zip',
                    'event_name' => EsdDownloadPaymentStatusPaidDisabledZipEvent::EVENT_NAME,
                    'action_name' => MailTemplateActions::MAIL_TEMPLATE_MAIL_SEND_ACTION,
                    'active' => 0,
                    'config' => json_encode([
                        'mail_template_type_id' => Uuid::fromBytesToHex($templateTypeId),
                        'mail_template_id' => Uuid::fromBytesToHex($templateId),
                    ]),
                    'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            );
        }
    }

    private function insertMailTemplate(string $templateId, string $templateTypeId, Connection $connection): void
    {
        $connection->insert(
            'mail_template',
            [
                'id' => $templateId,
                'mail_template_type_id' => $templateTypeId,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );

        $defaultLanguageId = Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);

        $englishLanguageId = $this->fetchLanguageIdByName(self::ENGLISH_LANGUAGE_NAME, $connection);
        $germanLanguageId = $this->fetchLanguageIdByName(self::GERMAN_LANGUAGE_NAME, $connection);

        $englishMailTemplate = [
            'subject' => 'Your download product of order {{ order.orderNumber }}',
            'description' => 'Download link template',
            'sender_name' => 'No Reply',
            'content_html' => EsdMailTemplate::getDownloadHtmlMailTemplateForDisabledZip(),
            'content_plain' => EsdMailTemplate::getDownloadPlainMailTemplateForDisabledZip(),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'mail_template_id' => $templateId,
        ];

        $germanMailTemplate = [
            'subject' => 'Ihr Download-Produkt der Bestellung {{ order.orderNumber }}',
            'description' => 'Linkvorlage herunterladen',
            'sender_name' => 'Keine Antwort',
            'content_html' => EsdMailTemplate::getDownloadHtmlMailTemplateForDisabledZipInGerman(),
            'content_plain' => EsdMailTemplate::getDownloadPlainMailTemplateForDisabledZipInGerman(),
            'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            'mail_template_id' => $templateId,
            'language_id' => $germanLanguageId,
        ];

        if (!\in_array($defaultLanguageId, [$englishLanguageId, $germanLanguageId], true)) {
            $connection->insert(
                'mail_template_translation',
                $englishMailTemplate + ['language_id' => $defaultLanguageId]
            );
        }

        if ($englishLanguageId) {
            $connection->insert(
                'mail_template_translation',
                $englishMailTemplate + ['language_id' => $englishLanguageId]
            );
        }

        if ($germanLanguageId) {
            $connection->insert(
                'mail_template_translation',
                $germanMailTemplate
            );
        }
    }

    private function insertMailTemplateType(string $templateTypeId, Connection $connection): void
    {
        $connection->insert(
            'mail_template_type',
            [
                'id' => $templateTypeId,
                'technical_name' => EsdMailTemplate::TEMPLATE_TYPE_DOWNLOAD_DISABLED_ZIP_TECHNICAL_NAME,
                'available_entities' => $this->getAvailableEntities(),
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );

        $defaultLanguageId = Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);

        $englishLanguageId = $this->fetchLanguageIdByName(
            self::ENGLISH_LANGUAGE_NAME,
            $connection
        );
        $germanLanguageId = $this->fetchLanguageIdByName(
            self::GERMAN_LANGUAGE_NAME,
            $connection
        );

        if (!\in_array($defaultLanguageId, [$englishLanguageId, $germanLanguageId], true)) {
            $connection->insert(
                'mail_template_type_translation',
                [
                    'mail_template_type_id' => $templateTypeId,
                    'language_id' => $defaultLanguageId,
                    'name' => EsdMailTemplate::TEMPLATE_TYPE_DOWNLOAD_DISABLED_ZIP_NAME,
                    'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            );
        }

        if ($englishLanguageId) {
            $connection->insert(
                'mail_template_type_translation',
                [
                    'mail_template_type_id' => $templateTypeId,
                    'language_id' => $englishLanguageId,
                    'name' => EsdMailTemplate::TEMPLATE_TYPE_DOWNLOAD_DISABLED_ZIP_NAME,
                    'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            );
        }

        if ($germanLanguageId) {
            $connection->insert(
                'mail_template_type_translation',
                [
                    'mail_template_type_id' => $templateTypeId,
                    'language_id' => $germanLanguageId,
                    'name' => EsdMailTemplate::TEMPLATE_TYPE_DOWNLOAD_DISABLED_ZIP_NAME_DE,
                    'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]
            );
        }
    }

    private function fetchTemplateTypeId(string $technicalName, Connection $connection): ?string
    {
        try {
            return (string) $connection->fetchOne(
                'SELECT `id`
            FROM `mail_template_type`
            WHERE `technical_name` = :technical_name LIMIT 1;',
                [
                    'technical_name' => $technicalName,
                ]
            );
        } catch (Exception $e) {
            return null;
        }
    }

    private function fetchTemplateId(string $templateTypeId, Connection $connection): ?string
    {
        try {
            return (string) $connection->fetchOne(
                'SELECT `id`
            FROM `mail_template`
            WHERE `mail_template_type_id` = :mail_template_type_id LIMIT 1',
                [
                    'mail_template_type_id' => $templateTypeId,
                ]
            );
        } catch (Exception $e) {
            return null;
        }
    }

    private function fetchLanguageIdByName(string $languageName, Connection $connection): ?string
    {
        try {
            return (string) $connection->fetchOne(
                'SELECT id FROM `language` WHERE `name` = :languageName',
                ['languageName' => $languageName]
            );
        } catch (Exception $e) {
            return null;
        }
    }

    private function getAvailableEntities(): string
    {
        return '{"order": "order", "salesChannel": "sales_channel"}';
    }
}
