<?php declare(strict_types=1);
namespace Sas\Esd\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1597597704CreateEsdMediaTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1597597704;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
            CREATE TABLE IF NOT EXISTS `sas_product_esd_media` (
              id BINARY(16) NOT NULL,
              `esd_id` binary(16) NOT NULL,
              `media_id` BINARY(16) NULL,
              `created_at` datetime(3) NOT NULL,
              `updated_at` datetime(3) DEFAULT NULL,
              PRIMARY KEY (id),
              CONSTRAINT `fk.esd_media.esd_id` FOREIGN KEY (`esd_id`)
                REFERENCES `sas_product_esd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
              CONSTRAINT `fk.esd_media.media_id` FOREIGN KEY (`media_id`)
                REFERENCES `media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;
        $connection->exec($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
