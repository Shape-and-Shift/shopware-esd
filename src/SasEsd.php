<?php declare(strict_types=1);

namespace Sas\Esd;

use Doctrine\DBAL\Connection;
use Sas\Esd\Service\EsdService;
use Sas\Esd\Utils\InstallUninstall;
use Sas\Esd\Utils\Update;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;

class SasEsd extends Plugin
{
    public function activate(ActivateContext $activateContext): void
    {
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

        /** @var EntityRepositoryInterface $mailTemplateTypeRepository */
        $mailTemplateTypeRepository = $this->container->get('mail_template_type.repository');

        /** @var EntityRepositoryInterface $mailTemplateRepository */
        $mailTemplateRepository = $this->container->get('mail_template.repository');

        (new InstallUninstall(
            $mailTemplateTypeRepository,
            $mailTemplateRepository
        ))->uninstall($context->getContext());

        $dirCompress = dirname(__DIR__, 4) . '/files/' . EsdService::FOLDER_COMPRESS_NAME;
        if (is_dir($dirCompress)) {
            $this->rmdirRecursive($dirCompress);
        }
    }

    public function update(UpdateContext $updateContext): void
    {
        (new Update())->update($this->container, $updateContext);

        parent::update($updateContext);
    }

    /**
     * We need to drop the database tables
     * in case if the plugin is uninstalled
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function dropDatabaseTable(): void
    {
        /** @var Connection $connection */
        $connection = $this->container->get(Connection::class);

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
        $connection->executeStatement('DROP TABLE IF EXISTS `sas_product_esd`');
        $connection->executeStatement('DROP TABLE IF EXISTS `sas_product_esd_order`');
        $connection->executeStatement('DROP TABLE IF EXISTS `sas_product_esd_serial`');
        $connection->executeStatement('DROP TABLE IF EXISTS `sas_product_esd_media`');
        $connection->executeStatement('DROP TABLE IF EXISTS `sas_product_esd_download_history`');
        $connection->executeStatement('DROP TABLE IF EXISTS `sas_product_esd_video`');
        $connection->executeStatement('ALTER TABLE `product` DROP COLUMN `esd`');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function rmdirRecursive($dir): void
    {
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            unlink("$dir/$file");
        }
        rmdir($dir);
    }
}
