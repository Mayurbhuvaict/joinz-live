<?php

namespace Kplngi\ProductOrder\Subscriber;

use Kplngi\ProductOrder\Position\ProductCategoryDefinition;
use Shopware\Core\Content\Category\SalesChannel\CachedCategoryRoute;
use Shopware\Core\Content\Category\SalesChannel\CachedNavigationRoute;
use Shopware\Core\Content\Product\SalesChannel\Listing\CachedProductListingRoute;
use Shopware\Core\Framework\Adapter\Cache\CacheInvalidator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OnPositionChange implements EventSubscriberInterface
{
    private const ENTITY_NAME = 'kplngi_productcategoryposition';

    private EntityRepositoryInterface $positionRepository;
    private CacheInvalidator $cacheInvalidator;

    public function __construct(
        EntityRepositoryInterface $positionRepository,
        CacheInvalidator $cacheInvalidator
    )
    {
        $this->positionRepository = $positionRepository;
        $this->cacheInvalidator = $cacheInvalidator;
    }

    public static function getSubscribedEvents()
    {
        return [
            EntityWrittenContainerEvent::class => 'onPositionWritten'
        ];
    }

    public function onPositionWritten(EntityWrittenContainerEvent $event): void
    {
        if (!$this->isPositionEntity($event)) {
            return;
        }

        $categoryIds = $this->getEffectedCategoryIds($event);

        $navigationIds = array_map([CachedNavigationRoute::class, 'buildName'], $categoryIds);
        $navigationIds[] = CachedNavigationRoute::BASE_NAVIGATION_TAG;
        $this->cacheInvalidator->invalidate($navigationIds);

        $this->cacheInvalidator->invalidate(
            array_map([CachedCategoryRoute::class, 'buildName'], $categoryIds)
        );

        $this->cacheInvalidator->invalidate(
            array_map([CachedProductListingRoute::class, 'buildName'], $categoryIds)
        );
    }

    private function isPositionEntity(EntityWrittenContainerEvent $event): bool
    {
        $entityEvent = $event->getEventByEntityName(self::ENTITY_NAME);

        if ($entityEvent === null) {
            return false;
        }

        return true;
    }

    private function getEffectedCategoryIds(EntityWrittenContainerEvent $event): array
    {
        $positionIds = $event->getPrimaryKeys(ProductCategoryDefinition::ENTITY_NAME);

        if (empty($positionIds)) {
            return [];
        }

        $criteria = new Criteria($positionIds);
        $positions = $this->positionRepository->search($criteria, $event->getContext());
        $categoryIds = [];

        /** @var ArrayEntity */
        foreach ($positions->getElements() as $position) {
            $categoryIds[] = $position->get('categoryId');
        }

        return $categoryIds;
    }
}
