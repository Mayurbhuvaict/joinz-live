<?php declare( strict_types = 1 );

namespace JoinzImportPlugin\Subscriber;

use DateTime;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Metric\EntityAggregation;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Product\ProductEvents;

class ProductSubscriber implements EventSubscriberInterface
{
	public static function getSubscribedEvents(): array
	{
		return [
			ProductEvents::PRODUCT_LOADED_EVENT => 'onProductsLoaded'
		];
	}

	public function onProductsLoaded( EntityLoadedEvent $event ): void
	{
		/** @var ProductEntity $productEntity */
		foreach ( $event->getEntities() as $productEntity ) {
			if ( empty( $productEntity->getDeliveryTime() ) ) {
				continue;
			}
			$min = $productEntity->getDeliveryTime()->getMin();
			$max = $productEntity->getDeliveryTime()->getMax();
			$productEntity->addExtension( 'delivery_date', new ArrayEntity( [
				'from' => $this->getDeliveryDate( $min ),
				'to' => $this->getDeliveryDate( $max ),
				'text' => $this->getDeliveryText( $min, $max ),
				'fastest_text' => $this->getDeliveryText($min)
			] ) );

		}
	}

	protected function getDeliveryText( $min, $max = null )
	{
		$minDate = $this->getDeliveryDate( $min );

		if (!is_null($max) && $min != $max ) {
			$maxDate = $this->getDeliveryDate( $max );
			return $minDate->format( 'jS F' ) . ' to ' . $maxDate->format( 'jS F' );
		}
		//return $minDate->format( 'D jS' ).' of '.$minDate->format('F'); transalte in dutch
        return $minDate->format( 'd-m' );
	}

	public static function getDeliveryDate( $orderDays )
	{
		$date = new DateTime();
		$date_timestamp = $date->getTimestamp();
		for ( $i = 0; $i < ( $orderDays ); $i++ ) {
			$nextDay = date( 'w', strtotime( '+1day', $date_timestamp ) );
			if ( $nextDay == 0 || ( $nextDay == 4 ) ) {
				$i--;
			}
			$date_timestamp = strtotime( '+1day', $date_timestamp );
		}
		$date->setTimestamp( $date_timestamp );
		return $date;
	}
}
