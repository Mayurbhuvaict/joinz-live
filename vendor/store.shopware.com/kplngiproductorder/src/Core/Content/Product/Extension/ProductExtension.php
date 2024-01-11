<?php

namespace Kplngi\ProductOrder\Core\Content\Product\Extension;

use Kplngi\ProductOrder\Position\Fields\PositionOneToMany;
use Kplngi\ProductOrder\Position\ProductCategoryDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Extension;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class ProductExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new PositionOneToMany(
                'kplngiPositions',
                ProductCategoryDefinition::class,
                'product_id',
                'id'
            ))->addFlags(new Extension())
        );
    }

    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }
}
