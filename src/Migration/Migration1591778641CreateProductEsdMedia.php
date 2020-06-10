<?php declare(strict_types=1);

namespace Sas\Esd\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1591778641CreateProductEsdMedia extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1591778641;
    }

    public function update(Connection $connection): void
    {
        $connection->executeQuery(
            '
            CREATE TABLE IF NOT EXISTS `sas_product_esd_media` (
                `id` BINARY(16) NOT NULL,
                `sas_product_esd_id` BINARY(16) NOT NULL,
                `media_id` BINARY(16) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');

        $connection->executeQuery('
            ALTER TABLE
              `sas_product_esd_media`
            ADD KEY `fk.sas_product_esd_media.sas_product_esd_id` (`sas_product_esd_id`),
            ADD KEY `fk.sas_product_esd_media.media_id` (`media_id`),
            ADD CONSTRAINT `fk.sas_product_esd_media.sas_product_esd_id` FOREIGN KEY (`sas_product_esd_id`) REFERENCES `sas_product_esd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
            ADD CONSTRAINT `fk.sas_product_esd_media.media_id` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
