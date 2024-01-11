<?php

namespace Kplngi\ProductOrder\Position\Fields;

use Kplngi\ProductOrder\Position\CategoryIdHelper;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\EntityDefinitionQueryHelper;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\FieldResolver\AbstractFieldResolver;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\FieldResolver\FieldResolverContext;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ReverseInherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\Uuid\Uuid;


class PositionResolver extends AbstractFieldResolver
{
    /**
     * @var CategoryIdHelper
     */
    private $categoryIdHelper;

    public function __construct(CategoryIdHelper $categoryIdHelper)
    {
        $this->categoryIdHelper = $categoryIdHelper;
    }

    public function join(FieldResolverContext $context): string
    {
        $field = $context->getField();

        if (!$field instanceof OneToManyAssociationField) {
            return $context->getAlias();
        }

        $context->getQuery()->addState(EntityDefinitionQueryHelper::HAS_TO_MANY_JOIN);

        $alias = $context->getAlias() . '.' . $field->getPropertyName();
        if ($context->getQuery()->hasState($alias)) {
            return $alias;
        }

        $context->getQuery()->addState($alias);

        $productJoin = '    
            (((`product`.`parent_id` IS NULL) AND `product`.`id` = `product.kplngiPositions`.`product_id` )
            OR
            ((`product`.`parent_id` IS NOT NULL) AND `product`.`parent_id` = `product.kplngiPositions`.`product_id`))
        ';

        $positionWhere = '`product.kplngiPositions`.`category_id`=:kplngiPositionCategory';

        $context->getQuery()->leftJoin(
            EntityDefinitionQueryHelper::escape($context->getAlias()),
            EntityDefinitionQueryHelper::escape($field->getReferenceDefinition()->getEntityName()),
            EntityDefinitionQueryHelper::escape($alias),
            $productJoin . ' AND ' . $positionWhere
        );

        $context->getQuery()->addOrderBy('`product.kplngiPositions`.`position`', 'DESC');

        $context->getQuery()->setParameter('kplngiPositionCategory', Uuid::fromHexToBytes($this->categoryIdHelper->getCategoryId()));

        return $alias;
    }
}
