<?php declare( strict_types = 1 );

namespace JoinzImportPlugin\Extension\Content\Product;

use Shopware\Core\Content\Product\Aggregate\ProductCrossSelling\ProductCrossSellingDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\SearchRanking;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ListField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\PasswordField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\System\NumberRange\DataAbstractionLayer\NumberRangeField;

class ProductHashDefinition extends EntityDefinition
{
    const ENTITY_NAME = 'product_hash';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ProductHashEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection( [
            ( new IdField( 'id', 'id' ) )->addFlags( new Required(), new PrimaryKey() ),
            new FkField( 'product_id', 'productId', ProductDefinition::class ),
            ( new StringField( 'hash', 'productHash' ) )->addFlags( new ApiAware() ),
            ( new NumberRangeField( 'product_number', 'productNumber' ) )->addFlags( new ApiAware(), new SearchRanking( SearchRanking::HIGH_SEARCH_RANKING ), new Required() ),

            new OneToOneAssociationField( 'product', 'product_id', 'id', ProductDefinition::class, false ),

        ] );
    }
}
