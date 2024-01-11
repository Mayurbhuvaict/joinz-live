<?php declare(strict_types=1);

namespace JoinzImportPlugin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1685636350ExpandProductNameOnStock extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1685636350;
    }

    public function update(Connection $connection): void
    {
        $query = <<<SQL
ALTER TABLE product_stock MODIFY COLUMN product_number VARCHAR(100);
SQL;

        $connection->exec($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
