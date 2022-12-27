<?php declare(strict_types=1);

namespace Sas\Esd\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Sas\Esd\Event\EsdDownloadPaymentStatusPaidDisabledZipEvent;
use Sas\Esd\Event\EsdDownloadPaymentStatusPaidEvent;
use Sas\Esd\Event\EsdSerialPaymentStatusPaidEvent;
use Sas\Esd\Utils\EsdMailTemplate;
use Shopware\Core\Content\Flow\Aggregate\FlowSequence\FlowSequenceDefinition;
use Shopware\Core\Content\Flow\FlowDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1672036378AddEsdFlowBuilder extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1672036378;
    }

    public function update(Connection $connection): void
    {
        $this->createDownloadForPaidStatus($connection);
        $this->createDownloadForPaidStatusWithDisabledZip($connection);
        $this->createSendSerialForPaidStatus($connection);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function createDownloadForPaidStatus(Connection $connection): void
    {
        $fetchTemplateTypeId = $this->fetchTemplateTypeId(EsdMailTemplate::TEMPLATE_TYPE_DOWNLOAD_TECHNICAL_NAME, $connection);
        if (!\is_string($fetchTemplateTypeId)) {
            return;
        }

        $mailTemplateId = $this->fetchTemplateId($fetchTemplateTypeId, $connection);
        if (!\is_string($mailTemplateId)) {
            return;
        }

        $flowId = Uuid::randomBytes();
        $connection->insert(
            FlowDefinition::ENTITY_NAME,
            [
                'id' => $flowId,
                'name' => 'ESD - download for paid status',
                'event_name' => EsdDownloadPaymentStatusPaidEvent::EVENT_NAME,
                'description' => '',
                'priority' => 100,
                'active' => 0,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );

        $connection->insert(
            FlowSequenceDefinition::ENTITY_NAME,
            [
                'id' => Uuid::randomBytes(),
                'flow_id' => $flowId,
                'action_name' => 'action.mail.send',
                'config' => \json_encode([
                    'mailTemplateId' => Uuid::fromBytesToHex($mailTemplateId),
                    'documentTypeIds' => [],
                    'recipient' => [
                        'type' => 'default',
                        'data' => [],
                    ],
                ]),
                'position' => 1,
                'display_group' => 1,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );
    }

    private function createDownloadForPaidStatusWithDisabledZip(Connection $connection): void
    {
        $fetchTemplateTypeId = $this->fetchTemplateTypeId(EsdMailTemplate::TEMPLATE_TYPE_DOWNLOAD_DISABLED_ZIP_TECHNICAL_NAME, $connection);
        if (!\is_string($fetchTemplateTypeId)) {
            return;
        }

        $mailTemplateId = $this->fetchTemplateId($fetchTemplateTypeId, $connection);
        if (!\is_string($mailTemplateId)) {
            return;
        }

        $flowId = Uuid::randomBytes();
        $connection->insert(
            FlowDefinition::ENTITY_NAME,
            [
                'id' => $flowId,
                'name' => 'ESD - download for paid status (disabled zip)',
                'event_name' => EsdDownloadPaymentStatusPaidDisabledZipEvent::EVENT_NAME,
                'description' => '',
                'priority' => 100,
                'active' => 0,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );

        $connection->insert(
            FlowSequenceDefinition::ENTITY_NAME,
            [
                'id' => Uuid::randomBytes(),
                'flow_id' => $flowId,
                'action_name' => 'action.mail.send',
                'config' => \json_encode([
                    'mailTemplateId' => Uuid::fromBytesToHex($mailTemplateId),
                    'documentTypeIds' => [],
                    'recipient' => [
                        'type' => 'default',
                        'data' => [],
                    ],
                ]),
                'position' => 1,
                'display_group' => 1,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );
    }

    private function createSendSerialForPaidStatus(Connection $connection): void
    {
        $fetchTemplateTypeId = $this->fetchTemplateTypeId(EsdMailTemplate::TEMPLATE_TYPE_SERIAL_TECHNICAL_NAME, $connection);
        if (!\is_string($fetchTemplateTypeId)) {
            return;
        }

        $mailTemplateId = $this->fetchTemplateId($fetchTemplateTypeId, $connection);
        if (!\is_string($mailTemplateId)) {
            return;
        }

        $flowId = Uuid::randomBytes();
        $connection->insert(
            FlowDefinition::ENTITY_NAME,
            [
                'id' => $flowId,
                'name' => 'ESD - send serial for paid status',
                'event_name' => EsdSerialPaymentStatusPaidEvent::EVENT_NAME,
                'description' => '',
                'priority' => 100,
                'active' => 0,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );

        $connection->insert(
            FlowSequenceDefinition::ENTITY_NAME,
            [
                'id' => Uuid::randomBytes(),
                'flow_id' => $flowId,
                'action_name' => 'action.mail.send',
                'config' => \json_encode([
                    'mailTemplateId' => Uuid::fromBytesToHex($mailTemplateId),
                    'documentTypeIds' => [],
                    'recipient' => [
                        'type' => 'default',
                        'data' => [],
                    ],
                ]),
                'position' => 1,
                'display_group' => 1,
                'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
            ]
        );
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
}
