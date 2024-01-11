<?php

namespace JoinzImportPlugin\Subscriber;

use Shopware\Core\Content\Product\Events\ProductListingCollectFilterEvent;
use Shopware\Core\Content\Product\SalesChannel\Listing\Filter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\AvgAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\MaxAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\StatsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FilterSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProductListingCollectFilterEvent::class => 'handleFilter'
        ];
    }

    public function handleFilter(ProductListingCollectFilterEvent $event): void {
        $this->addMoqFilter($event);
        $this->addDeliveryTimeFilter($event);
        $this->addInStockFilter($event);
    }

    public function addInStockFilter(ProductListingCollectFilterEvent $event): void
    {
        $filters = $event->getFilters();
        $request = $event->getRequest();
        $stock = [
            'min' => $request->get('min-stock')
        ];

        $range = new RangeFilter('product.stock', [
            RangeFilter::GTE => $stock['min']
        ]);
        $stockFilter = new Filter(
            'stock',
            !empty($stock['min']),
            [
                new StatsAggregation('stock', 'product.stock')
            ],
            $range,
            $stock
        );
        //dd($stockFilter);
        $filters->add($stockFilter);
    }

    public function addMoqFilter(ProductListingCollectFilterEvent $event): void
    {
        $filters = $event->getFilters();
        $request = $event->getRequest();
        $ids = $request->get('moq');
        $moqFilter = new Filter(
            'moq',
            !empty($ids),
            [
                new TermsAggregation(
                    'moq',
                    'product.minPurchase',
                )
            ],
            new RangeFilter('product.minPurchase', [
                RangeFilter::LTE => max(explode('|', $ids))
            ]),
            $ids
        );

        $filters->add($moqFilter);
    }

    public function addDeliveryTimeFilter(ProductListingCollectFilterEvent $event): void
    {
        $filters = $event->getFilters();
        $request = $event->getRequest();
        $ids = $request->get('delivery-time');
        $deliveryTimeFilter = new Filter(
            'delivery-time',
            !empty($ids),
            [
                new EntityAggregation('delivery-time', 'product.deliveryTimeId', 'delivery_time')
            ],
            new EqualsAnyFilter('product.deliveryTimeId', explode('|', $ids)),
            $ids
        );

        $filters->add($deliveryTimeFilter);
    }
}
