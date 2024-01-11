<?php declare(strict_types=1);

namespace Kplngi\ProductOrder\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1580372363ProductCategoryPosition extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1580372363;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `kplngi_productcategoryposition` (
                `id` BINARY(16) NOT NULL,
                `product_id` BINARY(16) NOT NULL,
                `category_id` BINARY(16) NOT NULL,
                `position` INTEGER NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.product_category_position.product_id`
                    FOREIGN KEY (`product_id`)
                    REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk.product_category_position.category_id`
                    FOREIGN KEY (`category_id`)
                    REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');

        $connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `kplngi_orderactive` (
                `id` BINARY(16) NOT NULL,
                `category_id` BINARY(16) NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3) NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk.kplngi_orderactive.category_id`
                    FOREIGN KEY (`category_id`)
                    REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ');
    }


    public function updateDestructive(Connection $connection): void
    {

    }
}
