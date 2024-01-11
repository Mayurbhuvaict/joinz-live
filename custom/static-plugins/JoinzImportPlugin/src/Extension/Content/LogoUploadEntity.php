<?php declare( strict_types = 1 );

namespace JoinzImportPlugin\Extension\Content;

use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Struct\Struct;

class LogoUploadEntity extends Entity
{
    use EntityIdTrait;

    const ENTITY_NAME = 'logo_uploads';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function addExtensions(array $extensions): void
    {

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
