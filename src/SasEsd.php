<?php declare(strict_types=1);
namespace Sas\Esd;

use Doctrine\DBAL\Connection;
use Sas\Esd\Service\EsdService;
use Sas\Esd\Update\Update;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Shopware\Core\Framework\Uuid\Uuid;

class SasEsd extends Plugin
{
    public function activate(ActivateContext $activateContext): void
    {
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

        $dirCompress = dirname(__DIR__, 4) . '/files/' . EsdService::FOLDER_COMPRESS_NAME;
        if (is_dir($dirCompress)) {
            $this->rmdirRecursive($dirCompress);
        }
    }

    private function rmdirRecursive($dir) {
        foreach(scandir($dir) as $file) {
            if ('.' === $file || '..' === $file) continue;
            if (is_dir("$dir/$file")) rmdir_recursive("$dir/$file");
            else unlink("$dir/$file");
        }
        rmdir($dir);
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
        $connection->executeQuery('DROP TABLE IF EXISTS `sas_product_esd_media`');
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

    public function update(UpdateContext $updateContext): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        (new Update())->update($connection, $updateContext);
        parent::update($updateContext);
    }
}
