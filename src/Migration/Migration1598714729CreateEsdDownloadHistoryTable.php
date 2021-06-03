<?php declare(strict_types=1);

namespace Sas\Esd\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1598714729CreateEsdDownloadHistoryTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1598714729;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `sas_product_esd_download_history` (
                `id` binary(16) NOT NULL,
                `esd_order_id` binary(16) NOT NULL,
                `created_at` datetime(3) NOT NULL,
                `updated_at` datetime(3) DEFAULT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.esd_download_history.esd_order_id` FOREIGN KEY (`esd_order_id`)
                    REFERENCES `sas_product_esd_order` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
