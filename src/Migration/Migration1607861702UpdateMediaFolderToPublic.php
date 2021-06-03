<?php declare(strict_types=1);

namespace Sas\Esd\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Sas\Esd\Content\Product\Extension\Esd\EsdDefinition;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1607861702UpdateMediaFolderToPublic extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1607861702;
    }

    public function update(Connection $connection): void
    {
        $defaultFolderId = $this->fetchDefaultFolder($connection);
        if ($defaultFolderId) {
            $mediaFolderConfigId = $this->fetchMediaFolderConfig($defaultFolderId, $connection);
            if ($mediaFolderConfigId) {
                $sqlString = '
                    UPDATE `media_folder_configuration`
                    SET `private` = 0
                    WHERE `id`= :id';
                $connection->executeStatement($sqlString, [
                    'id' => $mediaFolderConfigId,
                ]);
            }
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }

    private function fetchDefaultFolder(Connection $connection): ?string
    {
        try {
            return (string) $connection->fetchOne(
                'SELECT id FROM `media_default_folder` WHERE `entity` = :entityName',
                ['entityName' => EsdDefinition::ENTITY_NAME]
            );
        } catch (Exception $e) {
            return null;
        }
    }

    private function fetchMediaFolderConfig(string $defaultFolderId, Connection $connection): ?string
    {
        try {
            return (string) $connection->fetchOne(
                'SELECT `media_folder_configuration_id` FROM `media_folder` WHERE `default_folder_id` = :defaultFolderId',
                ['defaultFolderId' => $defaultFolderId]
            );
        } catch (Exception $e) {
            return null;
        }
    }
}
