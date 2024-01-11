<?php declare( strict_types = 1 );

namespace JoinzImportPlugin\Extension\Content\Product;

use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Struct\Struct;

class ProductCrossSellingExtraEntity extends Entity
{
	use EntityIdTrait;

	protected $coverId;
	protected $cover;

	const ENTITY_NAME = 'product_cross_selling_extra';

	public function getEntityName(): string
	{
		return self::ENTITY_NAME;
	}


	/*
	 * @return ProductMediaEntity
	 */
	public function getCover()
    {
        return $this->cover;
    }

	public function setCover(ProductMediaEntity $cover)
	{
		$this->cover = $cover;
	}

	public function getCoverId()
    {
        return $this->coverId;
    }

	public function setCoverId(string $coverId)
	{
		$this->coverId = $coverId;
	}

	public function addExtensions(array $extensions): void
	{
		return;
	}

	public function getExtension(string $name): ?Struct
	{
		return null;
	}

	public function hasExtension(string $name): bool
	{
		return false;
	}

	public function getExtensions(): array
	{

	}
}