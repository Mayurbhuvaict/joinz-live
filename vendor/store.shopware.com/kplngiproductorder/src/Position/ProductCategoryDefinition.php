<?php

namespace Kplngi\ProductOrder\Position;

use Shopware\Core\Content\Category\CategoryDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductCategoryDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'kplngi_productcategoryposition';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new Required()),
            (new FkField('category_id', 'categoryId', CategoryDefinition::class))->addFlags(new Required()),
            (new IntField('position', 'position'))->addFlags(new Required()),
            (new ManyToOneAssociationField('product', 'product_id', ProductDefinition::class, 'id')),
            (new ManyToOneAssociationField('category', 'category_id', CategoryDefinition::class, 'id')),
        ]);
    }
}
