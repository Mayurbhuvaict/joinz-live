<?php declare(strict_types=1);

namespace Kplngi\ProductOrder;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class KplngiProductOrder extends Plugin
{
    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            parent::uninstall($uninstallContext);

            return;
        }

        /** @var Connection */
        $connection = $this->container->get(Connection::class);

        $connection->executeStatement('
            DROP TABLE IF EXISTS `kplngi_productcategoryposition`
        ');

        $connection->executeStatement('
            DROP TABLE IF EXISTS `kplngi_orderactive`
        ');
    }
}
