<?php declare(strict_types=1);

namespace Sas\Esd\Utils;

use Doctrine\DBAL\Connection;
use Psr\Container\ContainerInterface;
use Sas\Esd\Content\Product\Extension\Esd\Aggregate\EsdMedia\EsdMediaDefinition;
use Sas\Esd\Content\Product\Extension\Esd\EsdDefinition;
use Sas\Esd\Migration\Migration1597597704CreateEsdMediaTable;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Uuid\Uuid;

class Update
{
    public function update(ContainerInterface $container, UpdateContext $updateContext): void
    {
        if (\version_compare($updateContext->getCurrentPluginVersion(), '1.1.0', '<')) {
            $this->updateTo110($container);
        }
    }

    private function updateTo110(ContainerInterface $container): void
    {
        /** @var Connection $connection */
        $connection = $container->get(Connection::class);

        $esdMediaEntityName = EsdMediaDefinition::ENTITY_NAME;
        if (!$connection->getSchemaManager()->tablesExist([$esdMediaEntityName])) {
            $esdMediaMigration = new Migration1597597704CreateEsdMediaTable();
            $esdMediaMigration->update($connection);
        }

        $query = $connection->createQueryBuilder();
        $query->select([
            'LOWER(HEX(id)) as id',
            'LOWER(HEX(media_id)) as media_id',
        ]);
        $query->where($query->expr()->isNotNull('media_id'));
        $query->from(EsdDefinition::ENTITY_NAME);

        $esdList = $query->execute()->fetchAllAssociative();
        foreach ($esdList as $esd) {
            $id = Uuid::fromHexToBytes(Uuid::randomHex());
            $connection->insert(EsdMediaDefinition::ENTITY_NAME, [
                'id' => $id,
                'esd_id' => Uuid::fromHexToBytes($esd['id']),
                'media_id' => Uuid::fromHexToBytes($esd['media_id']),
            ]);
            $connection->update(EsdDefinition::ENTITY_NAME, ['media_id' => null], ['id' => Uuid::fromHexToBytes($esd['id'])]);
        }
    }
}
