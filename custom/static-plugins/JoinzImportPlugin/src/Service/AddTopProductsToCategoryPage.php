<?php declare( strict_types = 1 );

namespace JoinzImportPlugin\Service;

use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddTopProductsToCategoryPage implements EventSubscriberInterface
{
	protected $categoryRepository;
	private $productRepository;

	public function __construct( EntityRepositoryInterface $categoryRepository, EntityRepositoryInterface $productRepository )
	{
		$this->categoryRepository = $categoryRepository;
		$this->productRepository = $productRepository;
	}

	public static function getSubscribedEvents(): array
	{
		return [
			NavigationPageLoadedEvent::class => 'addTopProducts'
		];
	}

	public function addTopProducts( NavigationPageLoadedEvent $event ): void
	{
		if ( $event->getPage()->getCmsPage()->getType() == 'product_list' ) {
			$customFields = $event->getPage()->getHeader()->getNavigation()->getActive()->getTranslated()['customFields'];
			foreach ( (array)$customFields as $name => $value ) {

				if ($name == 'custom_top_products_category_products' || $name == 'custom_secondary_template_categories') {



				$context = $event->getContext();
				/** @var ProductEntity $productEntity */
				foreach ( $value as $k => $v ) {
					$criteria = new Criteria( [$v] );
					$criteria->addAssociation( 'cover' );
					$criteria->addAssociation( 'prices' );
					$criteria->addAssociation( 'calculatedPrices' );
					$criteria->addAssociation( 'deliveryTime' );
					$criteria->addAssociation( 'configuratorSettings' );
					$criteria->addAssociation( 'configuratorSettings.option' );
					$product = $this->productRepository
						->search( $criteria, $context )->first();

					if ( $product ) {
						$customFields[ $name ][ $k ] = $product;
					}
				}

                /** @var CategoryEntity $category */
                foreach ( $value as $k => $v ) {
                    $criteria = new Criteria( [$v] );
                    $criteria->addAssociation( 'cover' );
                    $criteria->addAssociation('media');
                    $category = $this->categoryRepository
                        ->search( $criteria, $context )->first();

                    if ( $category ) {
                        $customFields[ $name ][ $k ] = $category;
                    }
                }
                }
                else {
                    continue;
                }
			}
			$event->getPage()->getHeader()->getNavigation()->getActive()->setCustomFields( $customFields );

		}

	}
}
