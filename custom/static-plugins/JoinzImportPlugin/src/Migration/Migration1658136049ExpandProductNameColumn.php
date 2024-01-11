<?php declare(strict_types=1);

namespace JoinzImportPlugin\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1658136049ExpandProductNameColumn extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1658136049;
    }

    public function update(Connection $connection): void
    {
        // implement update
		//

		$query = <<<SQL
ALTER TABLE product MODIFY COLUMN product_number VARCHAR(100);
ALTER TABLE product_hash MODIFY COLUMN product_number VARCHAR(100);
SQL;

		$connection->exec($query);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
