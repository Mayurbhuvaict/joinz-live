<?php declare( strict_types = 1 );

namespace JoinzImportPlugin\Extension\Content;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\ExtendedProductDefinition;

class LogoUploadExtension extends EntityExtension
{
    public function extendFields( FieldCollection $collection ): void
    {
        $collection->add(
            new OneToOneAssociationField('toOne', 'id', 'media_id', MediaDefinition::class, false)
        );
    }

    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }
}
