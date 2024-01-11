<?php declare(strict_types=1);

namespace JoinzImportPlugin;

use Shopware\Core\Framework\Plugin;

class JoinzImportPlugin extends Plugin
{
	public function getMigrationNamespace(): string
	{
		return 'JoinzImportPlugin\Migration';
	}
}
