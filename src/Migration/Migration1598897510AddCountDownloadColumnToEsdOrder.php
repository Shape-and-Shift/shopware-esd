<?php declare(strict_types=1);

namespace Sas\Esd\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1598897510AddCountDownloadColumnToEsdOrder extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1598897510;
    }

    public function update(Connection $connection): void
    {
        $connection->executeUpdate('
            ALTER TABLE `sas_product_esd_order`
            ADD COLUMN `count_download` INT(11) NOT NULL DEFAULT 0 AFTER `serial_id`
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
