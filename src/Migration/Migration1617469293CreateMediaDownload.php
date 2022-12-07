<?php declare(strict_types=1);

namespace Sas\Esd\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1617469293CreateMediaDownload extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1617469293;
    }

    public function update(Connection $connection): void
    {
        try {
            $connection->executeStatement('
                ALTER TABLE `sas_product_esd_media`
                ADD COLUMN `download_limit_number` INT(11) DEFAULT NULL AFTER `media_id`
            ');
        } catch (Exception $e) {
        }

        try {
            $connection->executeStatement('
                CREATE TABLE IF NOT EXISTS `sas_product_esd_media_download_history` (
                    `id` binary(16) NOT NULL,
                    `esd_order_id` binary(16) NOT NULL,
                    `esd_media_id` binary(16) NOT NULL,
                    `created_at` datetime(3) NOT NULL,
                    `updated_at` datetime(3) DEFAULT NULL,
                    PRIMARY KEY (`id`),
                    CONSTRAINT `fk.esd_media_history.esd_order_id` FOREIGN KEY (`esd_order_id`)
                        REFERENCES `sas_product_esd_order` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                    CONSTRAINT `fk.esd_media_history.esd_media_id` FOREIGN KEY (`esd_media_id`)
                        REFERENCES `sas_product_esd_media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ');
        } catch (Exception $e) {
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
