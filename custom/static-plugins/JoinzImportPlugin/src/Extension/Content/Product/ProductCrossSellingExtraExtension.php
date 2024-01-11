<?php declare( strict_types = 1 );

namespace JoinzImportPlugin\Extension\Content\Product;

use Shopware\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductCrossSellingExtraExtension extends EntityExtension
{
	public function extendFields( FieldCollection $collection ): void
	{
		$collection->add(
//			new OneToManyAssociationField( 'imprintLocations', ImprintLocationDefinition::class, 'product_id', 'id' )
			new OneToOneAssociationField( 'extra', 'id', 'product_cross_selling_id', ProductCrossSellingExtraDefinition::class, false )
		);
	}

	public function getDefinitionClass(): string
	{
		return ProductCrossSellingDefinition::class;
	}
}