<?php declare( strict_types = 1 );

namespace JoinzImportPlugin\Extension\Content;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class LogoUploadDefinition extends EntityDefinition
{
    const ENTITY_NAME = 'logo_uploads';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return LogoUploadEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection( [
            ( new IdField( 'id', 'id' ) )->addFlags( new Required(), new PrimaryKey() ),
            ( new FkField( 'media_id', 'mediaId', ProductMediaDefinition::class ) ),
            ( new StringField( 'first_name', 'firstName' ) )->addFlags( new ApiAware() ),
            ( new StringField( 'last_name', 'lastName' ) )->addFlags( new ApiAware() ),
            ( new StringField( 'email', 'email' ) )->addFlags( new ApiAware() ),
            ( new StringField( 'additional_info', 'additionalInfo' ) )->addFlags( new ApiAware() ),


            new OneToOneAssociationField( 'cover', 'media_id', 'id', MediaDefinition::class, true )
        ] );
    }
}
