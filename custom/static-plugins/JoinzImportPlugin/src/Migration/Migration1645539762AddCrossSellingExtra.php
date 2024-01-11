<?php declare(strict_types=1);

namespace JoinzImportPlugin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

class Migration1645539762AddCrossSellingExtra extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1645539762;
    }

    public function update(Connection $connection): void
    {
		$query = <<<SQL
CREATE TABLE IF NOT EXISTS `product_cross_selling_extra` (
    `id` BINARY(16) NOT NULL,
    `product_cross_selling_id` BINARY(16) NULL,
    `media_id` BINARY(16) NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL;

		$connection->exec($query);

		//@TODO ImprintLocations
//		$defaultFolderId = Uuid::randomHex();
//		$mediaFolderId = Uuid::randomHex();
//
//		$query = <<<SQL
//INSERT INTO media_default_folder
//SQL;

    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
