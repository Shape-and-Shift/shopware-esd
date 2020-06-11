<?php declare(strict_types=1);

namespace Sas\Esd\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1591698930CreateEsdTable extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1591698930;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
DROP TABLE IF EXISTS `sas_product_esd`;
CREATE TABLE `sas_product_esd` (
  `id` binary(16) NOT NULL,
  `product_id` binary(16) NOT NULL,
  `product_version_id` binary(16) NOT NULL,
  `media_id` binary(16) DEFAULT NULL,
  `has_serial` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime(3) NOT NULL,
  `updated_at` datetime(3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk.sas_product_esd.product_id` (`product_id`,`product_version_id`),
  KEY `media_id` (`media_id`),
  CONSTRAINT `fk.sas_product_esd.product_id` FOREIGN KEY (`product_id`, `product_version_id`) REFERENCES `product` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `sas_product_esd_ibfk_1` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `sas_product_esd_order`;
CREATE TABLE `sas_product_esd_order` (
  `id` binary(16) NOT NULL,
  `esd_id` binary(16) NOT NULL,
  `order_line_item_id` binary(16) NOT NULL,
  `serial_id` binary(16) DEFAULT NULL,
  `created_at` datetime(3) NOT NULL,
  `updated_at` datetime(3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk.sas_product_esd_order.esd_id` (`esd_id`),
  KEY `order_line_item_id` (`order_line_item_id`),
  CONSTRAINT `fk.sas_product_esd_order.esd_id` FOREIGN KEY (`esd_id`) REFERENCES `sas_product_esd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


DROP TABLE IF EXISTS `sas_product_esd_serial`;
CREATE TABLE `sas_product_esd_serial` (
  `id` binary(16) NOT NULL,
  `esd_id` binary(16) NOT NULL,
  `serial` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime(3) NOT NULL,
  `updated_at` datetime(3) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk.sas_product_esd_serial.esd_id` (`esd_id`),
  CONSTRAINT `fk.sas_product_esd_serial.esd_id` FOREIGN KEY (`esd_id`) REFERENCES `sas_product_esd` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SQL;


        $connection->executeQuery($sql);
        $this->updateInheritance($connection, 'product', 'esd');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
