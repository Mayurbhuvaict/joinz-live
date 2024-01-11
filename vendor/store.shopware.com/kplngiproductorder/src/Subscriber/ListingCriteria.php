<?php

namespace Kplngi\ProductOrder\Subscriber;

use Kplngi\ProductOrder\Position\CategoryIdHelper;
use Kplngi\ProductOrder\Position\DisplayInStorefront;
use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Content\Product\Events\ProductListingResultEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Event\ShopwareEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class ListingCriteria implements EventSubscriberInterface
{
    /**
     * @var CategoryIdHelper
     */
    private $categoryIdHelper;

    /**
     * @var EntityRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderActiveRepository;

    public function __construct(
        CategoryIdHelper $categoryIdHelper,
        EntityRepositoryInterface $categoryRepository,
        EntityRepositoryInterface $orderActiveRepository
    )
    {
        $this->categoryIdHelper = $categoryIdHelper;
        $this->categoryRepository = $categoryRepository;
        $this->orderActiveRepository = $orderActiveRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            ProductListingCriteriaEvent::class => 'addCriteria',
            ProductListingResultEvent::class => 'handleCustomSorting',
        ];
    }

    public function handleCustomSorting(ProductListingResultEvent $event)
    {
        $categoryId = $this->getNavigationId($event->getRequest(), $event->getSalesChannelContext());

        if ($this->isCustomOrderListing($event, $categoryId)) {
            $event->getResult()->setSorting('');
        }
    }

    public function addCriteria(ProductListingCriteriaEvent $productListingCriteriaEvent)
    {
        $categoryId = $this->getNavigationId($productListingCriteriaEvent->getRequest(), $productListingCriteriaEvent->getSalesChannelContext());

        if (empty($categoryId)) {
            return;
        }

        if (!$this->isCustomOrderListing($productListingCriteriaEvent, $categoryId)) {
            return;
        }

        $productListingCriteriaEvent->getSalesChannelContext()->addExtension('kplngi_displaySorting', (new DisplayInStorefront())->assign(['displaySorting' => false]));

        $this->categoryIdHelper->setCategoryId($categoryId);

        $productListingCriteriaEvent->getCriteria()->resetSorting();
        $productListingCriteriaEvent->getCriteria()->addSorting(new FieldSorting('kplngiPositions.position', FieldSorting::DESCENDING));
        $productListingCriteriaEvent->getCriteria()->addSorting(new FieldSorting('product.id', FieldSorting::DESCENDING));
    }

    private function isCustomOrderListing(ShopwareEvent $event, $categoryId): bool
    {
        /** @var ParameterBag */
        $query = $event->getRequest()->query;

        if ($query->get('order')) {
            return false;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryId', $categoryId));

        $orderActive = $this->orderActiveRepository->search($criteria, $event->getContext());

        if ($orderActive->getTotal() === 0) {
            return false;
        }

        return true;
    }

    private function getNavigationId(Request $request, SalesChannelContext $salesChannelContext): string
    {
        $params = $request->attributes->get('_route_params');

        if ($params && isset($params['navigationId'])) {
            return $params['navigationId'];
        } elseif ($params && isset($params['categoryId'])) {
            return $params['categoryId'];
        }

        return $salesChannelContext->getSalesChannel()->getNavigationCategoryId();
    }
}
