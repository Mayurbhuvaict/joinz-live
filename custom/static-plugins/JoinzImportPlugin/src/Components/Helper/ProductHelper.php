<?php

namespace JoinzImportPlugin\Components\Helper;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class ProductHelper
{
    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    public function __construct(EntityRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    /**
     * @param $productId
     * @param $context
     * @return ProductEntity
     */
    public function getProductById($productId, $context)
    {
        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('cover');
        $criteria->addAssociation('seoUrls');
        $criteria->addAssociation('manufacturer');
        $criteria->addAssociation('productReviews');
        /** @var ProductCollection $productCollection */
        $productCollection = $this->productRepository->search($criteria, $context->getContext())->getEntities();
        return $productCollection->get($productId);
    }

}
