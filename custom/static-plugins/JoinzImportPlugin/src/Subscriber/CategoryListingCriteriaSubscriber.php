<?php declare( strict_types = 1 );

namespace JoinzImportPlugin\Subscriber;

use Shopware\Core\Content\Product\Events\ProductListingCriteriaEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CategoryListingCriteriaSubscriber implements EventSubscriberInterface
{
	public static function getSubscribedEvents(): array
	{
		return [
			ProductListingCriteriaEvent::class => 'onLoad'
		];
	}

	public function onLoad( ProductListingCriteriaEvent $event ): void
	{
		$criteria = $event->getCriteria();
		$criteria->addAssociation('configuratorSettings');
		$criteria->addAssociation('configuratorSettings.option');
//		$criteria->addAssociation('parent.configuratorSettings');
	}
}
