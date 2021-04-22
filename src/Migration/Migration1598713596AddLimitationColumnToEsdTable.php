<?php declare(strict_types=1);

namespace Sas\Esd\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1598713596AddLimitationColumnToEsdTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1598713596;
    }

    public function update(Connection $connection): void
    {
        try {
            $connection->executeUpdate('
                ALTER TABLE `sas_product_esd`
                ADD COLUMN `download_limit_number` INT(11) NOT NULL DEFAULT 1 AFTER `has_serial`,
                ADD COLUMN `has_unlimited_download` TINYINT(1) NOT NULL DEFAULT 0 AFTER `has_serial`,
                ADD COLUMN `has_custom_download_limit` TINYINT(1) NOT NULL DEFAULT 0 AFTER `has_serial`
            ');
        } catch (DBALException $e) {
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
