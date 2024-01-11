<?php

namespace JoinzImportPlugin\Service;

use JoinzImportPlugin\Service\Struct\ProductPageExtension;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AddProductVariations implements EventSubscriberInterface
{
	protected $productRepository;
	protected $shippingRepository;
	protected $cart;

	public function __construct( EntityRepositoryInterface $productRepository )
	{
		$this->productRepository = $productRepository;
	}

	public static function getSubscribedEvents(): array
	{
		return [
			ProductPageLoadedEvent::class => 'addProductVariations'
		];
	}

	public function addProductVariations( ProductPageLoadedEvent $event ): void
	{
		$product = $event->getPage()->getProduct();
		$parentId = $product->getParentId() ? $product->getParentId() : $product->getId();

		$criteria = new Criteria();
//		$criteria->addFilter(new RangeFilter('childCount', [RangeFilter::GT => 0]));
		$criteria->addFilter( new EqualsFilter( 'id', $parentId ) );
		$criteria->addAssociation( 'categories' );
		$criteria->addAssociation( 'categories.tags' );
		$criteria->addAssociation( 'deliveryTime' );
		$criteria->addAssociation( 'children' );
		$criteria->addAssociation( 'children.prices' );
		$criteria->addAssociation( 'children.deliveryTime' );
		$criteria->addAssociation( 'children.media' );
		$criteria->addAssociation( 'children.cover' );
		$criteria->addAssociation( 'children.crossSellings' );
		$criteria->addAssociation( 'children.crossSellings.extra' );
		$criteria->addAssociation( 'children.crossSellings.extra.cover' );
		$criteria->addAssociation( 'children.crossSellings.assignedProducts' );
		$criteria->addAssociation( 'children.crossSellings.assignedProducts.product' );
		$criteria->addAssociation( 'children.crossSellings.assignedProducts.product.prices' );
		$criteria->addAssociation( 'cover' );

		$product = $this->productRepository->search(
			$criteria,
			Context::createDefaultContext()
		)->first();

//		dd($this->cart);
//		dd($product->getChildren()->first()->getCrossSellings()->first()->getAssignedProducts()->first()->getProduct());

		$ppe = new ProductPageExtension( $product );
		$similarProducts = $this->getSimilarProducts( $event, $parentId );
		$ppe->setSimilarProducts( $similarProducts );
		$ppe->setShippingCost($this->getShippingCost($product));

		$event->getPage()->addExtension( 'joinzProduct', $ppe );

//		$dhl = $this->getDHL();
//		$dc = new \Shopware\Core\Checkout\Cart\Delivery\DeliveryCalculator;
//		$dc->calculate();
	}

	public function getShippingCost(ProductEntity $product){
		foreach($product->getCategories() as $cat){
			foreach($cat->getTags() as $tag){
				if($tag->name == 'FOOD/WINES-SPIRITS'){
					return 25;
				}
			}
		}
		return 0;
	}

	public function getSimilarProducts( $event, $parentId )
	{
		$product = $event->getPage()->getProduct();
		$catTree = $product->getCategoryTree();
		if(is_null($catTree)){
			return [];
		}
		$categoryId = end( $catTree );
		$criteria = new Criteria();
		$criteria->addFilter( new ContainsFilter( 'categoryTree', $categoryId ) );
		$criteria->addFilter( new NotFilter( NotFilter::CONNECTION_OR, [
			new EqualsFilter( 'parentId', null ),
			new EqualsFilter( 'parentId', $parentId )
		] ) );
		$criteria->addAssociation( 'categoryTree' );
		$criteria->addAssociation( 'cover' );
		$criteria->addAssociation( 'prices' );
		$criteria->addAssociation( 'deliveryTime' );
		$criteria->setLimit( 4 );
		$result = $this->productRepository->search( $criteria, $event->getContext() )->getElements();
        $criteria->addFilter( new NotFilter( NotFilter::CONNECTION_OR, [
            new EqualsFilter( 'parentId', null ),
            new EqualsFilter( 'parentId', array_key_first($result) )
        ] ) );
		return $this->productRepository->search( $criteria, $event->getContext() )->getElements();
	}

	public function getDHL()
	{
//		$criteria = new Criteria();
//		$criteria->addFilter( new EqualsFilter( 'name', 'DHL') );
//
//		return $this->shippingRepository->search( $criteria, Context::createDefaultContext() )->first();
	}
}
