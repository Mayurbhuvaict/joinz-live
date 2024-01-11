<?php

namespace Kplngi\ProductOrder\Controller;

use Kplngi\ProductOrder\Position\ProductCategoryPositionService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"api"})
 */
class ProductOrderController extends AbstractController
{
    /**
     * @var ProductCategoryPositionService
     */
    private $productCategoryPositionService;

    public function __construct(ProductCategoryPositionService $productCategoryPositionService)
    {
        $this->productCategoryPositionService = $productCategoryPositionService;
    }

    /**
     * @Route("api/kplngi/productorder/refresh/{categoryId}")
     */
    public function refreshCategoryProductOrder(string $categoryId, Context $context): Response
    {
        $this->productCategoryPositionService->refreshProductCategoryPositions($categoryId, $context);

        return new JsonResponse();
    }

    /**
     * @Route("api/kplngi/productorder/init/{categoryId}")
     */
    public function initializeCategoryProductOrder(string $categoryId, Context $context): Response
    {
        $this->productCategoryPositionService->initializeProductCategoryPositions($categoryId, $context);

        return new JsonResponse();
    }

    /**
     * @Route("api/kplngi/productorder/delete/{categoryId}")
     */
    public function deleteCategoryProductOrder(string $categoryId, Context $context): Response
    {
        $this->productCategoryPositionService->deleteProductCategoryPositions($categoryId, $context);

        return new JsonResponse();
    }
}
