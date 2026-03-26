<?php
declare(strict_types=1);

namespace Sas\Esd\Migration;

use Doctrine\DBAL\Connection;
use Sas\Esd\Utils\EsdMailTemplate;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1711756800UpdateSerialMailTemplateWithDownloadLinks extends MigrationStep
{
    private const LOCALE_EN = 'en-GB';
    private const LOCALE_DE = 'de-DE';

    public function getCreationTimestamp(): int
    {
        return 1711756800;
    }

    public function update(Connection $connection): void
    {
        $templateTypeId = $connection->fetchOne(
            'SELECT `id` FROM `mail_template_type` WHERE `technical_name` = :technical_name LIMIT 1',
            ['technical_name' => EsdMailTemplate::TEMPLATE_TYPE_SERIAL_TECHNICAL_NAME]
        );

        if (!$templateTypeId) {
            return;
        }

        $templateId = $connection->fetchOne(
            'SELECT `id` FROM `mail_template` WHERE `mail_template_type_id` = :typeId LIMIT 1',
            ['typeId' => $templateTypeId]
        );

        if (!$templateId) {
            return;
        }

        $englishLanguageId = $this->fetchLanguageIdByLocale(self::LOCALE_EN, $connection);
        $germanLanguageId = $this->fetchLanguageIdByLocale(self::LOCALE_DE, $connection);

        if ($englishLanguageId) {
            $connection->update(
                'mail_template_translation',
                [
                    'content_html' => EsdMailTemplate::getSerialHtmlMailTemplate(),
                    'content_plain' => EsdMailTemplate::getSerialPlainMailTemplate(),
                ],
                [
                    'mail_template_id' => $templateId,
                    'language_id' => $englishLanguageId,
                ]
            );
        }

        if ($germanLanguageId) {
            $connection->update(
                'mail_template_translation',
                [
                    'content_html' => EsdMailTemplate::getSerialHtmlMailTemplateInGerman(),
                    'content_plain' => EsdMailTemplate::getSerialPlainMailTemplateGerman(),
                ],
                [
                    'mail_template_id' => $templateId,
                    'language_id' => $germanLanguageId,
                ]
            );
        }
    }

    public function updateDestructive(Connection $connection): void
    {
    }

    private function fetchLanguageIdByLocale(string $localeCode, Connection $connection): ?string
    {
        $result = $connection->fetchOne(
            'SELECT `language`.`id`
             FROM `language`
             INNER JOIN `locale` ON `language`.`locale_id` = `locale`.`id`
             WHERE `locale`.`code` = :localeCode
             LIMIT 1',
            ['localeCode' => $localeCode]
        );

        return $result ?: null;
    }
}
