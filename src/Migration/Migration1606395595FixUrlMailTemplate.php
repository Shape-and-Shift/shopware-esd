<?php declare(strict_types=1);

namespace Sas\Esd\Migration;

use Doctrine\DBAL\Connection;
use Sas\Esd\Utils\EsdMailTemplate;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1606395595FixUrlMailTemplate extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1606395595;
    }

    public function update(Connection $connection): void
    {
        $templateTypeId = $connection->executeQuery(
            'SELECT `id` from `mail_template_type` WHERE `technical_name` = :type',
            ['type' => EsdMailTemplate::TEMPLATE_TYPE_DOWNLOAD_TECHNICAL_NAME]
        )->fetchOne();

        $templateId = $connection->executeQuery(
            'SELECT `id` from `mail_template` WHERE `mail_template_type_id` = :typeId',
            ['typeId' => $templateTypeId]
        )->fetchOne();

        if (!empty($templateId)) {
            $mailTemplateTranslations = $connection->executeQuery(
                'SELECT `language_id`, `content_html`, `content_plain` from `mail_template_translation` WHERE `mail_template_id` = :templateId',
                ['templateId' => $templateId]
            )->fetchAllAssociative();

            foreach ($mailTemplateTranslations as $mailTemplateTranslation) {
                $contentHtml = $this->replaceUrlToRawUrl($mailTemplateTranslation['content_html']);
                $contentPlain = $this->replaceUrlToRawUrl($mailTemplateTranslation['content_plain']);
                $this->updateMailTemplateContent($connection, $contentHtml, $contentPlain, $templateId, $mailTemplateTranslation['language_id']);
            }
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function updateMailTemplateContent(Connection $connection, string $contentHtml, string $contentPlain, string $templateId, string $languageId): void
    {
        $sqlString = '
        UPDATE `mail_template_translation`
        SET `content_html` = :contentHtml, `content_plain` = :contentPlain
        WHERE `mail_template_id`= :templateId AND `language_id` = :langId';
        $connection->executeStatement($sqlString, [
            'contentHtml' => $contentHtml,
            'contentPlain' => $contentPlain,
            'templateId' => $templateId,
            'langId' => $languageId,
        ]);
    }

    private function replaceUrlToRawUrl(string $content): string
    {
        $urlString = "url('frontend.sas.esd.download.guest', {esdOrderId: esdOrderId})";
        $rawString = "rawUrl('frontend.sas.esd.download.guest', {esdOrderId: esdOrderId}, salesChannel.domains|first.url)";
        if (strpos($content, $urlString) !== false) {
            return str_replace($urlString, $rawString, $content);
        }

        return $content;
    }
}
