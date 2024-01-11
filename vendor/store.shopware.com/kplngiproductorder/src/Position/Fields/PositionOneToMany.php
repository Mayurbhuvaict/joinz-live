<?php

namespace Kplngi\ProductOrder\Position\Fields;

use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;

class PositionOneToMany extends OneToManyAssociationField
{
    protected function getResolverClass(): ?string
    {
        return PositionResolver::class;
    }
}
