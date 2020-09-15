<?php declare(strict_types=1);

namespace Sas\Esd\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1599756148UpdateHasSerialDefaultValue extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1599756148;
    }

    public function update(Connection $connection): void
    {
        $connection->executeUpdate('
            ALTER TABLE `sas_product_esd`
            ALTER `has_serial` SET DEFAULT \'0\'
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
