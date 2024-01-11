<?php

namespace Kplngi\ProductOrder\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityDeletedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductCategoryChange implements EventSubscriberInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $productCategoryPositionRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $orderActiveRepository;
    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    public function __construct(
        EntityRepositoryInterface $entityRepository,
        EntityRepositoryInterface $orderActiveRepository,
        EntityRepositoryInterface $productRepository)
    {
        $this->productCategoryPositionRepository = $entityRepository;
        $this->orderActiveRepository = $orderActiveRepository;
        $this->productRepository = $productRepository;
    }

    public static function getSubscribedEvents()
    {
        return [
            'product_category.written' => 'onAssociationWritten',
            'product_category.deleted' => 'onAssociationDeleted'
        ];
    }

    public function onAssociationDeleted(EntityDeletedEvent $event)
    {
        foreach ($event->getIds() as $productCategoryDeleted) {
            $criteria = new Criteria();

            $criteria->addFilter(new EqualsFilter('productId', $productCategoryDeleted['productId']));
            $criteria->addFilter(new EqualsFilter('categoryId', $productCategoryDeleted['categoryId']));

            $positionsToDelete = $this->productCategoryPositionRepository->searchIds($criteria, $event->getContext());

            foreach ($positionsToDelete->getIds() as $positionIdToDelete) {
                $this->productCategoryPositionRepository->delete([
                    ['id' => $positionIdToDelete]
                ], $event->getContext());
            }
        }
    }

    public function onAssociationWritten(EntityWrittenEvent $event)
    {
        foreach ($event->getIds() as $productCategoryAdded) {
            $productCriteria = new Criteria();
            $productCriteria->addFilter(new EqualsFilter('id', $productCategoryAdded['productId']));

            $parentId = $this->productRepository->search($productCriteria, $event->getContext())->first()->getParentId();

            if ($parentId !== null) {
                continue;
            }

            $orderActiveCriteria = new Criteria();
            $orderActiveCriteria->addFilter(new EqualsFilter('categoryId', $productCategoryAdded['categoryId']));

            $orderActive = $this->orderActiveRepository->search($orderActiveCriteria, $event->getContext());

            if ($orderActive->getTotal() === 0) {
                continue;
            }

            $productOrderedCriteria = new Criteria();
            $productOrderedCriteria->addFilter(new EqualsFilter('productId', $productCategoryAdded['productId']));
            $productOrderedCriteria->addFilter(new EqualsFilter('categoryId', $productCategoryAdded['categoryId']));

            $productOrdered = $this->productCategoryPositionRepository->search($productOrderedCriteria, $event->getContext());

            if ($productOrdered->getTotal() === 0) {
                $this->productCategoryPositionRepository->create([
                    [
                        'productId' => $productCategoryAdded['productId'],
                        'categoryId' => $productCategoryAdded['categoryId'],
                        'position' => 0
                    ]
                ], $event->getContext());
            }
        }
    }
}
