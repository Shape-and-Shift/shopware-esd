<?php declare(strict_types=1);

namespace Sas\Esd;

use Doctrine\DBAL\Connection;
use Sas\Esd\Service\EsdService;
use Sas\Esd\Utils\InstallUninstall;
use Sas\Esd\Utils\Update;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\ActivateContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Plugin\Context\UpdateContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SasEsd extends Plugin
{
    public function activate(ActivateContext $activateContext): void
    {
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        \assert($this->container instanceof ContainerInterface);

        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        /* Drop the database tables */
        $this->dropDatabaseTable();

        \assert($this->container instanceof ContainerInterface);

        /** @var EntityRepository $mailTemplateTypeRepository */
        $mailTemplateTypeRepository = $this->container->get('mail_template_type.repository');

        /** @var EntityRepository $mailTemplateRepository */
        $mailTemplateRepository = $this->container->get('mail_template.repository');

        /** @var EntityRepository $flowRepository */
        $flowRepository = $this->container->get('flow.repository');
        (new InstallUninstall(
            $mailTemplateTypeRepository,
            $mailTemplateRepository,
            $flowRepository
        ))->uninstall($uninstallContext->getContext());

        $dirCompress = \dirname(__DIR__, 4) . '/files/' . EsdService::FOLDER_COMPRESS_NAME;
        if (is_dir($dirCompress)) {
            $this->rmdirRecursive($dirCompress);
        }
    }

    public function update(UpdateContext $updateContext): void
    {
        \assert($this->container instanceof ContainerInterface);

        (new Update())->update($this->container, $updateContext);

        parent::update($updateContext);
    }

    /**
     * We need to drop the database tables
     * in case if the plugin is uninstalled
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function dropDatabaseTable(): void
    {
        \assert($this->container instanceof ContainerInterface);
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

    private function rmdirRecursive(string $dir): void
    {
        $scandir = scandir($dir);
        if (!$scandir) {
            return;
        }

        foreach ($scandir as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            unlink("$dir/$file");
        }

        rmdir($dir);
    }
}
