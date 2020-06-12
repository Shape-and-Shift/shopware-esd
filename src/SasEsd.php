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
        /**
         * When the plugin is activated we
         */
        $indexerMessageSender = $this->container->get(IndexerMessageSender::class);
        $indexerMessageSender->partial(new \DateTimeImmutable(), [InheritanceIndexer::getName()]);
    }

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);

        /* create the private folder for the downloads */
        //$this->createPrivateFolder($installContext);
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

    /**
     * We need to drop the database tables
     * in case if the plugin is uninstalled
     */
    protected function dropDatabaseTable() :void
    {
        $connection = $this->container->get(Connection::class);

        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=0;');
        $connection->executeQuery('DROP TABLE IF EXISTS `sas_product_esd`');
        $connection->executeQuery('DROP TABLE IF EXISTS `sas_product_esd_order`');
        $connection->executeQuery('DROP TABLE IF EXISTS `sas_product_esd_serial`');
        $connection->executeUpdate('ALTER TABLE `product` DROP COLUMN `esd`');
        $connection->executeQuery('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * We need to create a private folder for the downloads,
     * otherwise the files would be accessible by public.
     * @param $installContext
     */
    public function createPrivateFolder(InstallContext $installContext)
    {
        /** @var EntityRepositoryInterface $mediaFolderRepository */
        $mediaFolderRepository = $this->container->get('media_folder.repository');

        $folderId = Uuid::randomHex();
        $configurationId = Uuid::randomHex();

        $mediaFolderRepository->create([
            [
                'entity'            => 'sas_product_esd',
                'name'              => 'ESD Media',
                'associationFields' => [],
                'folder'            => [
                    'id'              => $folderId,
                    'name'            => 'ESD Downloads',
                    'configurationId' => $configurationId,
                    'configuration'   =>
                        [
                            'id'      => $configurationId,
                            'private' => true,
                        ],
                ],
            ],
        ], $installContext->getContext());
    }
}
