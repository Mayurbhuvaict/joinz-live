<?php
namespace JoinzImportPlugin\Service\Struct;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Struct\Struct;

class ProductPageExtension extends Struct
{
	public $variations;

	public $product;

	public $similarProducts;

	public $shippingCost = 0;

	public function __construct( ProductEntity $product )
	{
		$this->product = $product;
	}

	public function setSimilarProducts( $similarProducts )
	{
		$this->similarProducts = $similarProducts;
	}

	public function setShippingCost($sc)
	{
		$this->shippingCost = $sc;
	}
}