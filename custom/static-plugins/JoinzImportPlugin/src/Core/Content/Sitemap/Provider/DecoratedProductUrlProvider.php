<?php

namespace JoinzImportPlugin\Core\Content\Sitemap\Provider;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Sitemap\Provider\AbstractUrlProvider;
use Shopware\Core\Content\Sitemap\Struct\UrlResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class DecoratedProductUrlProvider extends AbstractUrlProvider
{
	private $decoratedUrlProvider;
	protected static $nonSitemapIds = [];

	public function __construct( AbstractUrlProvider $abstractUrlProvider )
	{
		$this->decoratedUrlProvider = $abstractUrlProvider;
		if ( empty( static::$nonSitemapIds ) ) {
			static::loadNonSitemapIds();
		}
	}

	public function getDecorated(): AbstractUrlProvider
	{
		return $this->decoratedUrlProvider;
	}

	public function getName(): string
	{
		return $this->getDecorated()->getName();
	}

	public function getUrls( SalesChannelContext $context, int $limit, ?int $offset = null): UrlResult
	{
		$urlResult = $this->getDecorated()->getUrls($context, $limit, $offset);
		$urls = $urlResult->getUrls();

		$urls = array_filter($urls, function ( $el ){
				return ! in_array( strtoupper($el->getIdentifier()), static::$nonSitemapIds );
			}
		);

		/* Change $urls, e.g. removing entries or updating them by iterating over them. */

		return new UrlResult($urls, $urlResult->getNextOffset());
	}

protected static function loadNonSitemapIds()
{
	$connection = \Shopware\Core\Kernel::getConnection();
	/** @var QueryBuilder $queryBuilder */
	$queryBuilder = $connection->createQueryBuilder();

	$queryBuilder
		->select( 'HEX(id) as id' )
		->from( 'product' )
		->where( 'parent_id IS NULL AND cover IS NULL' );

	static::$nonSitemapIds = array_map(function($el){return $el['id'];},$queryBuilder->execute()->fetchAll());
}
}