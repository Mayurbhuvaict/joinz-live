<?php declare(strict_types=1);

namespace JoinzImportPlugin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1685633612AddProductStock extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1685633612;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
CREATE TABLE IF NOT EXISTS `product_stock` (
    `id` BINARY(16) NOT NULL,
    `product_id` BINARY(16) NULL,
    `product_number` VARCHAR(16) NULL,
    `stock` INT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL;

        $connection->exec($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
