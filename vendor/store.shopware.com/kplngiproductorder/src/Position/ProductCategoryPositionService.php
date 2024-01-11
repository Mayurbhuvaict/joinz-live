<?php

namespace Kplngi\ProductOrder\Position;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;

class ProductCategoryPositionService
{
    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;
    /**
     * @var EntityRepositoryInterface
     */
    private $categoryRepository;
    /**
     * @var EntityRepositoryInterface
     */
    private $productCategoryPositionRepository;
    /**
     * @var EntityRepositoryInterface
     */
    private $productOrderActiveRepository;

    public function __construct(
        EntityRepositoryInterface $productRepository,
        EntityRepositoryInterface $categoryRepository,
        EntityRepositoryInterface $productCategoryPositionRepository,
        EntityRepositoryInterface $productOrderActiveRepository
    )
    {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->productCategoryPositionRepository = $productCategoryPositionRepository;
        $this->productOrderActiveRepository = $productOrderActiveRepository;
    }

    public function deleteProductCategoryPositions(string $categoryId, Context $context): void
    {
        $sortedProducts = $this->productCategoryPositionRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('categoryId', $categoryId)), $context);

        foreach ($sortedProducts->getIds() as $categoryPositionId) {
            $this->productCategoryPositionRepository->delete([['id' => $categoryPositionId]], $context);
        }

        $orderActive = $this->productOrderActiveRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('categoryId', $categoryId)), $context);

        foreach ($orderActive->getIds() as $orderActiveId) {
            $this->productOrderActiveRepository->delete([['id' => $orderActiveId]], $context);
        }
    }

    public function initializeProductCategoryPositions(string $categoryId, Context $context): void
    {
        $categoryProducts = $this->getCategoryProducts($categoryId, $context);

        if (!$categoryProducts) {
            return;
        }

        foreach ($categoryProducts->getElements() as $productId => $product) {
            $this->productCategoryPositionRepository->create([[
                'productId' => $productId,
                'categoryId' => $categoryId,
                'position' => 0
            ]], $context);
        }

        $this->productOrderActiveRepository->create([[
            'categoryId' => $categoryId
        ]], $context);
    }

    public function refreshProductCategoryPositions(string $categoryId, Context $context): void
    {
        $categoryProducts = $this->getCategoryProducts($categoryId, $context);

        if ($categoryProducts === null) {
            return;
        }

        $categoryProductsPositions = $this->getCategoryProductsPositions($categoryId, $context);

        if ($categoryProductsPositions === null) {
            return;
        }

        $productsCurrentlyOrdered = [];

        foreach ($categoryProductsPositions->getElements() as $id => $categoryProduct) {
            $position = $categoryProduct->get('position');

            if (array_key_exists($categoryProduct->get('productId'), $productsCurrentlyOrdered)) {
                $currentPosition = $productsCurrentlyOrdered[$categoryProduct->get('productId')]['position'];

                if ($position > $currentPosition) {
                    $productsCurrentlyOrdered($categoryProduct->get('productId'))['position'] = $currentPosition;
                }

                $this->productCategoryPositionRepository->delete([
                    ['id' => $id]
                ], $context);
                continue;
            }
            $productsCurrentlyOrdered[$categoryProduct->get('productId')] = ['positionId' => $id, 'position' => $position];
        }

        foreach ($categoryProducts->getElements() as $productId => $product) {
            if (array_key_exists($productId, $productsCurrentlyOrdered)) {
                continue;
            }
            $this->productCategoryPositionRepository->create([[
                'productId' => $productId,
                'categoryId' => $categoryId,
                'position' => 0
            ]], $context);
        }

        foreach ($productsCurrentlyOrdered as $productId => $sortedProduct) {
            if (array_key_exists($productId, $categoryProducts->getElements())) {
                continue;
            }
            $this->productCategoryPositionRepository->delete([
                ['id' => $sortedProduct['positionId']]
            ], $context);
        }
    }

    private function getCategoryProductsPositions(string $categoryId, Context $context): ?EntityCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryId', $categoryId));

        $productCategoryPositions = $this->productCategoryPositionRepository->search($criteria, $context);

        if ($productCategoryPositions->count() === 0) {
            return null;
        }

        return $productCategoryPositions->getEntities();
    }

    private function getCategoryProducts(string $categoryId, Context $context): ?EntityCollection
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('product.categoriesRo.id', $categoryId));
        $criteria->addFilter(new EqualsFilter('product.parentId', null));

        $data = $this->productRepository->search($criteria, $context);
        if ($data->getTotal() > 0) {
            return $data->getEntities();
        }
        return null;
    }
}
