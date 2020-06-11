<?php declare(strict_types=1);

namespace Sas\Esd;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\Indexer\InheritanceIndexer;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\MessageQueue\IndexerMessageSender;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Uuid\Uuid;

class SasEsd extends Plugin
{
    public function activate(ActivateContext $activateContext): void
    {
        $indexerMessageSender = $this->container->get(IndexerMessageSender::class);
        $indexerMessageSender->partial(new \DateTimeImmutable(), [InheritanceIndexer::getName()]);
    }

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
    }

    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);

        if ($context->keepUserData()) {
            return;
        }

        /* Drop the database tables */
        $this->dropDatabaseTable();
    }

    protected function dropDatabaseTable() :void
    {
        $connection = $this->container->get(Connection::class);

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0;');
        $connection->executeQuery('DROP TABLE IF EXISTS `sas_product_esd`');
        $connection->executeQuery('DROP TABLE IF EXISTS `sas_product_esd_media`');
        $connection->executeUpdate('ALTER TABLE `product` DROP COLUMN `esd`');
        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function nix()
    {
        /** @var EntityRepositoryInterface $mediaFolderRepository */
        $mediaFolderRepository = $this->container->get('media_folder.repository');

        $folderId = Uuid::randomHex();
        $configurationId = Uuid::randomHex();

        $mediaFolderRepository->create([
            [
                'entity' => 'sas_downloads',
                'associationFields' => ['documents'],
                'folder' => [
                    'name' => 'ESD Downloads',
                    'useParentConfiguration' => false,
                    'configuration' =>
                        [
                            'private' => true,
                            'createThumbnails' => false,
                        ]
                ]
            ],
        ], $installContext->getContext());
    }
}
