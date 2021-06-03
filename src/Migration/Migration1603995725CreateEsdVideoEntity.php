<?php declare(strict_types=1);

namespace Sas\Esd\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1603995725CreateEsdVideoEntity extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1603995725;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `sas_product_esd_video` (
              `id` binary(16) NOT NULL,
              `esd_media_id` binary(16) NOT NULL,
              `option` int(3) NOT NULL DEFAULT 0,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `fk.sas_product_esd_video.esd_media_id` (`esd_media_id`),
              CONSTRAINT `fk.sas_product_esd_video.esd_media_id` FOREIGN KEY (`esd_media_id`) REFERENCES `sas_product_esd_media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
