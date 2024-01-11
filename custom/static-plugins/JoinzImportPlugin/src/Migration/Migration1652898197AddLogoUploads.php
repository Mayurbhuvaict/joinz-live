<?php declare(strict_types=1);

namespace JoinzImportPlugin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1652898197AddLogoUploads extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1652898197;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
CREATE TABLE IF NOT EXISTS `logo_uploads` (
    `id` BINARY(16) NOT NULL,
    `media_id` BINARY(16) NULL,
    `first_name` VARCHAR(255) NULL,
    `last_name` VARCHAR(255) NULL,
    `email` VARCHAR(255) NULL,
    `additional_info` TEXT NULL,
    `created_at` DATETIME(3) NOT NULL,
    `updated_at` DATETIME(3) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB
SQL;

        $connection->exec($query);    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
