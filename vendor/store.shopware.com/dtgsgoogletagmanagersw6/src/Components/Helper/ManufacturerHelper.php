<?php
/**
 * Created by PhpStorm.
 * User: constantin
 * Date: 28.02.17
 * Time: 15:35
 */
namespace Dtgs\GoogleTagManager\Components\Helper;

use Shopware\Core\Content\Category\CategoryCollection;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerCollection;
use Shopware\Core\Content\Product\Aggregate\ProductManufacturer\ProductManufacturerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ManufacturerHelper
{

    public function __construct(EntityRepositoryInterface $manufacturerRepository)
    {
        $this->manufacturerRepository = $manufacturerRepository;
    }

    /**
     * @var EntityRepositoryInterface
     */
    private EntityRepositoryInterface $manufacturerRepository;

    /**
     * @param $manufacturerId
     * @param SalesChannelContext $context
     * @return ProductManufacturerEntity
     */
    public function getManufacturerById($manufacturerId, $context): ProductManufacturerEntity
    {
        $criteria = new Criteria([$manufacturerId]);
        /** @var ProductManufacturerCollection $manufacturerCollection */
        $manufacturerCollection = $this->manufacturerRepository->search($criteria, $context->getContext())->getEntities();
        return $manufacturerCollection->get($manufacturerId);
    }

}
