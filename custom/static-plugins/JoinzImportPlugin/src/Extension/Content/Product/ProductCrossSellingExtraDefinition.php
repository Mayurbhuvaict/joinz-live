<?php declare( strict_types = 1 );

namespace JoinzImportPlugin\Extension\Content\Product;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductCrossSellingExtraDefinition extends EntityDefinition
{
	const ENTITY_NAME = 'product_cross_selling_extra';

	public function getEntityName(): string
	{
		return self::ENTITY_NAME;
	}

	public function getEntityClass(): string
	{
		return ProductCrossSellingExtraEntity::class;
	}

	protected function defineFields(): FieldCollection
	{
		return new FieldCollection( [
			( new IdField( 'id', 'id' ) )->addFlags( new Required(), new PrimaryKey() ),
			new FkField( 'product_cross_selling_id', 'productCrossSellingId', ProductDefinition::class ),
			( new FkField( 'media_id', 'coverId', ProductMediaDefinition::class ) ),

			// Working for manyToOne
			// new ManyToOneAssociationField( 'product', 'product_id', ProductDefinition::class,'id', true )
			new OneToOneAssociationField( 'productCrossSelling', 'product_cross_selling_id', 'id', ProductCrossSellingDefinition::class, false ),
			new OneToOneAssociationField( 'cover', 'media_id', 'id', MediaDefinition::class, true )
		] );
	}
}