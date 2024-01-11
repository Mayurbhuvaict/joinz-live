<?php

namespace JoinzImportPlugin\Service;

use Doctrine\DBAL\Query\QueryBuilder;
use Exception;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Property\PropertyGroupDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Output\OutputInterface;

class ImportService
{

	protected $productRepository;
	protected $taxRepository;
	protected $mediaRepository;
	protected $propertyGroupRepository;
	protected $propertyGroupOptionRepository;
	protected $productCrossSellingRepository;
	protected $productConfiguratorSettingRepository;
	protected $salesChannelRepository;
	protected $ruleRepository;
	protected $categoryRepository;
	protected $importImageService;
	protected $productHashRepository;
	protected $deliveryTimeRepository;
	protected $productPriceRepository;
	protected $productOptionRepository;
	protected $productPropertyRepository;

	protected $imprintsMap = [];

	protected $mediaIds = [];

	protected $context;
	protected $salesChannelId;
	protected $taxId;
	protected $ruleId;

	protected $output;
	protected $verbose;

	public function __construct( EntityRepositoryInterface $productRepository,
								 EntityRepositoryInterface $taxRepository,
								 EntityRepositoryInterface $mediaRepository,
								 EntityRepositoryInterface $productMediaRepository,
								 EntityRepositoryInterface $propertyGroupRepository,
								 EntityRepositoryInterface $propertyGroupOptionRepository,
								 EntityRepositoryInterface $productCrossSellingRepository,
								 EntityRepositoryInterface $productConfiguratorSettingRepository,
								 EntityRepositoryInterface $salesChannelRepository,
								 EntityRepositoryInterface $ruleRepository,
								 EntityRepositoryInterface $categoryRepository,
								 ImportImage $importImageService,
								 EntityRepositoryInterface $productHashRepository,
								 EntityRepositoryInterface $deliveryTimeRepository,
								 EntityRepositoryInterface $productPriceRepository,
								 EntityRepositoryInterface $productOptionRepository,
								 EntityRepositoryInterface $productPropertyRepository
	)
	{
		$this->productRepository = $productRepository;
		$this->taxRepository = $taxRepository;
		$this->mediaRepository = $mediaRepository;
		$this->productMediaRepository = $productMediaRepository;
		$this->propertyGroupRepository = $propertyGroupRepository;
		$this->propertyGroupOptionRepository = $propertyGroupOptionRepository;
		$this->productCrossSellingRepository = $productCrossSellingRepository;
		$this->productConfiguratorSettingRepository = $productConfiguratorSettingRepository;
		$this->salesChannelRepository = $salesChannelRepository;
		$this->ruleRepository = $ruleRepository;
		$this->categoryRepository = $categoryRepository;
		$this->importImageService = $importImageService;
		$this->productHashRepository = $productHashRepository;
		$this->deliveryTimeRepository = $deliveryTimeRepository;
		$this->productPriceRepository = $productPriceRepository;
		$this->productOptionRepository = $productOptionRepository;
		$this->productPropertyRepository = $productPropertyRepository;
	}

	public function import( $json_string, OutputInterface $output, $withImages = false, $hash = false, $verbose = false, $hashEntity = false )
	{
		try {
			$this->output = $output;
			$this->verbose = $verbose;

			$this->verbose( '========================BEGIN======================' );
			//TRANSACTIONS NOT WORKING BECAUSE OF IMPLICIT COMMIT ???
//			/** @var  \Doctrine\DBAL\Connection $connection */
//			$connection = \Shopware\Core\Kernel::getConnection();
//			$connection->beginTransaction();
			$obj = json_decode( $json_string );

			if ( ! isset( $this->context ) ) {
				$this->context = Context::createDefaultContext();
			}

			$this->verbose( 'Checking product exists.. ' . $obj->Sku );
			$productId = $this->checkProductExist( $obj->Sku );
			$this->verbose( 'Checking product exists done. ' . $productId );
			$existingProduct = true;

			if ( ! $productId ) {
				$productId = Uuid::randomHex();
				$existingProduct = false;
			}
			if ( $existingProduct ) {
//				$this->verbose('Existing product. Switching withImages = false.' );
//				$withImages = false;
			}

			if ( ! $existingProduct ) {
				// only update, prevent new imports

//				$this->verbose('Product does not exist. Skipping.' );
//				return;
			}

			if ( ! isset( $this->salesChannelId ) ) {
				$this->verbose( 'Getting Sales Channel Id..' );
				$this->salesChannelId = $this->getSalesChannelId();
			}
			if ( ! isset( $this->taxId ) ) {
				$this->verbose( 'Getting Tax Id..' );
				$this->taxId = $this->getTaxId();
			}
			if ( ! isset( $this->ruleId ) ) {
				$this->verbose( 'Getting Rule Id..' );
				$this->ruleId = $this->getRuleId();
			}

			$this->verbose( 'Getting Category Id..' );
			//$categoryId = $this->getCategoryId( $obj->NonLanguageDependedProductDetails->Category );
			// All new imports go to 'uncategorized'
			$categoryId = null;
			if ( ! $existingProduct ) {
				$categoryId = $this->getCategoryIdByName( 'uncategorized' );
			}

			$this->verbose( 'Getting Delivery Time Id..' );
			$deliveryTimeId = $this->getDeliveryTimeId();
			//@TODO handle image filename
			$this->verbose( 'Deleting Product Prices...' );
			$this->deleteProductPrices( $productId );
			$this->verbose( 'Deleting Product Options...' );
			$this->deleteProductOptions( $productId );
			$this->verbose( 'Deleting Product Properties...' );
			$this->deleteProductProperties( $productId );
			$this->verbose( 'Getting Product Prices...' );
			$productPrices = $this->getProductPrices( $obj );
			$this->verbose( 'Generating Product Data...' );

			$productData = [
				'id' => $productId,
				'parentId' => null,
				'name' => $obj->ProductDetails->nl->Name,
				'description' => $obj->ProductDetails->nl->Description,
				'productNumber' => $obj->Sku,
				'stock' => 999,
				'prices' => $productPrices,
				'price' => [$productPrices[ 0 ][ 'price' ][ 0 ]],
				'taxId' => $this->taxId,
				'purchaseSteps' => $this->getLocalized( $obj->ProductPriceCountryBased )->QuantityIncrements ?? NULL,
//			'optionIds' => [
//				''
//			]
			];

			if ( isset( $obj->ProductDetails->nl->UnstructuredInformation->ItemWeightWithAccessoriesKG ) ) {
				$productData[ 'weight' ] = (int)$obj->ProductDetails->nl->UnstructuredInformation->ItemWeightWithAccessoriesKG / 1000;
			}

			if ( isset( $obj->ProductDetails->nl->UnstructuredInformation->OuterCartonWidthCM ) ) {
				$productData[ 'width' ] = (int)$obj->ProductDetails->nl->UnstructuredInformation->OuterCartonWidthCM * 10;
			}

			if ( isset( $obj->ProductDetails->nl->UnstructuredInformation->OuterCartonHeightCM ) ) {
				$productData[ 'height' ] = (int)$obj->ProductDetails->nl->UnstructuredInformation->OuterCartonHeightCM * 10;
			}

			if ( isset( $obj->ProductDetails->nl->UnstructuredInformation->OuterCartonLengthCM ) ) {
				$productData[ 'length' ] = (int)$obj->ProductDetails->nl->UnstructuredInformation->OuterCartonLengthCM * 10;
			}


			if ( $categoryId && ! $existingProduct ) {
				$productData[ 'categories' ] = [
					['id' => $categoryId]
				];
			}

			if ( ! $existingProduct ) {
				$productData[ 'visibilities' ] = [
					[
						'salesChannelId' => $this->salesChannelId,
						'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
					],
				];
				$productData[ 'minPurchase' ] = $this->getLocalized( $obj->ProductPriceCountryBased )->MinimumOrderQuantity ?? NULL;
				$productData[ 'deliveryTimeId' ] = $deliveryTimeId;
			}

			$this->verbose( 'Upserting product....' );
			$this->productRepository->upsert( [$productData], $this->context );

			if ( $withImages ) {
				$this->verbose( 'Deleting Product media....' );
				$this->deleteProductMedia( $productId );

				$this->verbose( 'Filling Product images....' );
				$this->fillProductImages( $productId, $obj );
			}

			$productOptionIds = [];
			$configuratorSettings = [];
			$childIds = [];
			$this->deleteRemovedChildProducts( $productId, $obj );
			foreach ( $obj->ChildProducts as $k => $childProduct ) {
				if ( $childProduct->Sku == $obj->Sku ) {
					$childProduct->Sku .= '-' . $k;
				}
				$cppcb = $this->getLocalized( $childProduct->ProductPriceCountryBased );

				if ( ! isset( $childProduct->ProductDetails->nl ) ) {
					$this->verbose( 'ChildProduct doesn\'t have ProductDetails->nl' );
					continue;
				}
				$this->verbose( 'Checking if child exists... ' . $childProduct->Sku );
				$childId = $this->checkProductExist( $childProduct->Sku );

				$this->verbose( 'Check done.' );
				$existingChildProduct = true;

				if ( ! $childId ) {
					$childId = Uuid::randomHex();
					$existingChildProduct = false;
				}

				$childIds[] = $childId;

				//@TODO translations
				$configurationFields = $childProduct->ProductDetails->nl->ConfigurationFields;
				$optionIds = [];
				if ( empty( $configurationFields ) ) {
					$configurationFields = [
						(object)[
							'ConfigurationNameTranslated' => 'joinz-variant',
							'ConfigurationValue' => 'general'
						]
					];
				}
				//add search color filter
                $colorHex = false;
                if ( isset( $childProduct->UnstructuredInformation->HexColor ) ) {
                    $colorHex = $childProduct->UnstructuredInformation->HexColor;
                }
                $this->verbose( 'Getting Search color id' );
                $searchColor = false;
                if (isset($childProduct->NonLanguageDependedProductDetails->SearchColor)) {
                    $searchColor = $childProduct->NonLanguageDependedProductDetails->SearchColor;
                }
                $searchColorOptionId = $this->getPropertyOptionId('Color search',$searchColor, $colorHex);
                $this->verbose('Got it.');
                $optionIds[] = ['id' => $searchColorOptionId];
                $productOptionIds[ $searchColorOptionId ] = 1;
                $configuratorSettings[] = ['optionId' => $searchColorOptionId];
                //WebShopInformation
                $webShopInformationTypes = $childProduct->ProductDetails->nl->WebShopInformation;
                //filters
                //Material
                //InkColor
                foreach ($webShopInformationTypes as $wsit) {
                    $name = $wsit->InformationLabel;
                    $value = $wsit->InformationValue;
                    if(strlen($value) > 254) {
                        continue;
                    }
                    $this->verbose('Getting webshopinfo id');
                    $wsiId = $this->getPropertyOptionId($name, $value);
                    $this->verbose('Got it.');
                    $optionIds[] = ['id' => $wsiId];
                    $productOptionIds[ $wsiId ] = 1;
                    $configuratorSettings[] = ['optionId' => $wsiId];
                }
				foreach ( $configurationFields as $configField ) {
					$configName = $configField->ConfigurationNameTranslated;
					$configValue = $configField->ConfigurationValue;
					$colorHex = false;
					if ( isset( $childProduct->UnstructuredInformation->HexColor ) ) {
						$colorHex = $childProduct->UnstructuredInformation->HexColor;
					}
					$this->verbose( 'Getting Property Option Id' );
					$optionId = $this->getPropertyOptionId( $configName, $configValue, $colorHex );
					$this->verbose( 'Got it.' );

					$configuratorSetting = [
						'optionId' => $optionId
					];

					$optionIds[] = ['id' => $optionId];
					$productOptionIds[ $optionId ] = 1;
					$configuratorSettings[] = $configuratorSetting;
				}

				$this->deleteProductPrices( $childId );
				$this->deleteProductOptions( $childId );
				$this->deleteProductProperties( $childId );
				$productPrices = $this->getProductPrices( $childProduct );
				$childData = [
					'id' => $childId,
					'name' => $childProduct->ProductDetails->nl->Name,
					'description' => $childProduct->ProductDetails->nl->Description,
					'productNumber' => $childProduct->Sku,
					'stock' => 999,
					'prices' => $productPrices,
					'price' => [$productPrices[ 0 ][ 'price' ][ 0 ]],
					'taxId' => $this->taxId,
					'purchaseSteps' => $cppcb->QuantityIncrements ?? NULL,
					'parentId' => $productId,
					'options' => $optionIds,
				];

				if ( ! $existingChildProduct ) {
					if ( isset( $productData[ 'minPurchase' ] ) ) {
						$childData[ 'minPurchase' ] = $cppcb->MinimumOrderQuantity ?? NULL;
						$childData[ 'deliveryTimeId' ] = $deliveryTimeId;

						// Inheritance
						$childData[ 'minPurchase' ] = $childData[ 'minPurchase' ] == $productData[ 'minPurchase' ] ? NULL : $childData[ 'minPurchase' ];
						$childData[ 'deliveryTimeId' ] = $childData[ 'deliveryTimeId' ] == $productData[ 'deliveryTimeId' ] ? NULL : $childData[ 'deliveryTimeId' ];
					}
				}

				if ( isset( $obj->ProductDetails->nl->UnstructuredInformation->ItemWeightWithAccessoriesKG ) ) {
					$childData[ 'weight' ] = (int)$obj->ProductDetails->nl->UnstructuredInformation->ItemWeightWithAccessoriesKG / 1000;
				}

				if ( isset( $obj->ProductDetails->nl->UnstructuredInformation->OuterCartonWidthCM ) ) {
					$childData[ 'width' ] = (int)$obj->ProductDetails->nl->UnstructuredInformation->OuterCartonWidthCM * 10;
				}

				if ( isset( $obj->ProductDetails->nl->UnstructuredInformation->OuterCartonHeightCM ) ) {
					$childData[ 'height' ] = (int)$obj->ProductDetails->nl->UnstructuredInformation->OuterCartonHeightCM * 10;
				}

				if ( isset( $obj->ProductDetails->nl->UnstructuredInformation->OuterCartonLengthCM ) ) {
					$childData[ 'length' ] = (int)$obj->ProductDetails->nl->UnstructuredInformation->OuterCartonLengthCM * 10;
				}

				// Inheritance
				$childData[ 'purchaseSteps' ] = $childData[ 'purchaseSteps' ] == $productData[ 'purchaseSteps' ] ? NULL : $childData[ 'purchaseSteps' ];

				$this->verbose( 'Upserting Child Data...' );
				$this->productRepository->upsert( [$childData], $this->context );

				if ( $withImages ) {
					$this->verbose( 'Deleting Product media...' );
					$this->deleteProductMedia( $childId );

					$this->verbose( 'Filling child images...' );
					$this->fillProductImages( $childId, $childProduct );
				}

				$this->verbose( 'Deleting Product crosssellings...' );
				$this->deleteProductCrossSellings( $childId );


				if ( is_array( $childProduct->ImprintPositions ) ) {
					foreach ( $childProduct->ImprintPositions as $imprintPosition ) {

						$locationTexts = $imprintPosition->ImprintLocationTexts;
						$imprintOptions = $imprintPosition->ImprintOptions;
						$i = 0;

						$imprints = [];
						$crossSellings = [];

						//Deduplicate SKUs for suppliers:
						$suppliers = ["A24-", "A496", "A508"];

						if ( in_array( substr( $productData[ 'productNumber' ], 0, 4 ), $suppliers ) ) {
							foreach ( $imprintOptions as $imprintOption ) {
								$imprintOption->Sku = $this->deduplicateImprintSku( $imprintOption );
							}
						}

						foreach ( $imprintOptions as $imprintOption ) {
							$i++;

							// Already inserted, don't insert again but add to crossSellings
							if ( isset( $this->imprintsMap[ $imprintOption->Sku ] ) ) {
								$this->verbose( 'Imprint Already exists.' );
								$imprintId = $this->imprintsMap[ $imprintOption->Sku ];
							} else {
								$this->verbose( 'Checking Imprint Option Product exists...' . $imprintOption->Sku );
								$imprintId = $this->checkProductExist( $imprintOption->Sku );
								$this->imprintsMap[ $imprintOption->Sku ] = $imprintId;

								$existingImprint = true;

								if ( ! $imprintId ) {
									$imprintId = Uuid::randomHex();
									$existingImprint = false;
								}

								$numColors = $imprintOption->PrintColor;
								$name = $imprintOption->ImprintTexts->nl->Name;

								$setupCost = $this->calcImprintSetupCost( $imprintOption );
								$this->getImprintSetupCostProduct( $setupCost );

								$this->verbose( 'Deleting product prices...' );
								$this->deleteProductPrices( $imprintId );
								$this->verbose( 'Extracting product prices...' );
								$imprintPrices = $this->getProductPrices( $imprintOption );
								$imprintData = [
									'id' => $imprintId,
									'name' => $name,
									'description' => $imprintOption->ImprintTexts->nl->Description,
									'productNumber' => $imprintOption->Sku,
									'stock' => 999,
									'prices' => $imprintPrices,
									'price' => [$imprintPrices[ 0 ][ 'price' ][ 0 ]],
									'taxId' => $this->taxId,
									'height' => $imprintOption->DimensionsHeight ?? null,
									'width' => $imprintOption->DimensionsWidth ?? null,
									'length' => $imprintOption->DimensionsLength ?? null,
									'weight' => $imprintOption->weight ?? null,
									'customFields' => [
										'numColors' => $numColors,
										'DimensionsDepth' => $imprintOption->DimensionsDepth ?? null,
										'DimensionsDiameter' => $imprintOption->DimensionsDiameter ?? null,
										'MaxPrintArea' => $imprintOption->UnstructuredInformation->MaxPrintArea ?? null,
										'isImprint' => true,
										'imprint_setupCost' => $setupCost
									],
								];

								// Do not show in search
								if ( ! $existingImprint ) {
									$imprintData[ 'visibilities' ] = [
										[
											'salesChannelId' => $this->salesChannelId,
											'visibility' => ProductVisibilityDefinition::VISIBILITY_LINK,
										],
									];
								}
								$imprints[] = $imprintData;
							}

							$crossSellings[] = [
								'productId' => $imprintId,
								'position' => $i,
							];
						}


						if ( ! empty( $imprints ) ) {
							$this->verbose( 'Upserting Imprint Data Product...' );
							$this->productRepository->upsert( $imprints, $this->context );
						}

						$newCrossSelling = [
							'productId' => $childId,
							'active' => true,
							'name' => $locationTexts->nl->Name,
							'type' => 'productList',
							'position' => 1,
							'assignedProducts' => $crossSellings,
						];

						if ( isset( $locationTexts->nl->Images[ 0 ] ) ) {
							$imprintLocationImageUrl = $locationTexts->nl->Images[ 0 ]->Url;
							$this->verbose( 'Handling Imprint Location Media...' );
							$imprintMediaId = $this->createOrGetMediaId( $imprintLocationImageUrl, 'product_cross_selling' );
							$newCrossSelling[ 'extra' ] = [
								'coverId' => $imprintMediaId
							];
						}

						$this->verbose( 'Creating new cross selling' );
						$this->productCrossSellingRepository->create( [$newCrossSelling], $this->context );
					}
				}
			}
			$productOptionIds = array_keys( $productOptionIds );
			$this->verbose( 'Updating Product properties & configuration settings' );

			$configuratorSettings = array_unique( $configuratorSettings, SORT_REGULAR );
			$updateConfiguratorSettings = [];
			$this->deleteProductConfiguratorSettings( $productId );
			$updateConfiguratorSettings[] = [
				'id' => $productId,
				'properties' => array_map( function ( $el ) {
					return ['id' => $el];
				}, $productOptionIds ),
				'configuratorSettings' => $configuratorSettings
			];
			foreach ( $childIds as $childId ) {
				$this->deleteProductConfiguratorSettings( $childId );
				$updateConfiguratorSettings[] = [
					'id' => $childId,
					'configuratorSettings' => $configuratorSettings
				];
			}
			$this->productRepository->update( $updateConfiguratorSettings, $this->context );

			if ( $hash ) {
				$hashData = [
					'id' => $hashEntity ? $hashEntity->getId() : Uuid::randomHex(),
					'productNumber' => $obj->Sku,
					'productHash' => $hash,
					'productId' => $productId,
				];

				$this->verbose( 'Upserting product hash.' );
				$this->productHashRepository->upsert( [$hashData], $this->context );
			}
//			$connection->commit();
			$this->verbose( '========================END======================' );
		} catch ( Exception $e ) {
//			$connection->rollBack();
			throw $e;
		}
	}

	public function getImprintSetupCostProduct( int $setupCost )
	{
		$sku = "JN_SETUP_COST_$setupCost";
		$productId = $this->checkProductExist( $sku );

		if ( $productId ) {
			$this->verbose( "ImprintCost found $sku." );
			return $productId;
		}

		$productId = Uuid::randomHex();
		$this->verbose( "Creating Imprint Cost $sku." );
		$imprintCostData = [
			'id' => $productId,
			'parentId' => null,
			'name' => "Setup Cost $setupCost",
			'description' => "Setup Cost $setupCost",
			'productNumber' => $sku,
			'stock' => 999999,
			'price' => [[
				"currencyId" => Defaults::CURRENCY,
				"gross" => $setupCost * 1.21,
				"net" => $setupCost,
				"linked" => true,
			]],
			'taxId' => $this->taxId,
			'purchaseSteps' => 1,
			'visibilities' => [
				[
					'salesChannelId' => $this->salesChannelId,
					'visibility' => ProductVisibilityDefinition::VISIBILITY_LINK,
				]
			],
		];
		$this->productRepository->create( [$imprintCostData], $this->context );
		return $productId;
	}

	public function calcImprintSetupCost( $imprintOption )
	{
		$setupCost = 0;

		foreach ( $imprintOption->ImprintCosts as $imprintCost ) {
			// https://api.promidata.app/V2ModelInfo/html/T_PromiDataBaseModel_HelpClasses_ImprintCostCalculationType.htm
			if ( $imprintCost->CalculationType != 'Unique' ) {
				continue;
			}

			$ppcb = $this->getLocalized( $imprintCost->ProductPriceCountryBased );
			$cost = $ppcb->RecommendedSellingPrice[ 0 ]->Price;
			$setupCost += $cost * $imprintCost->CalculationAmount;
		}

		return (int)$setupCost;
	}

	public function deduplicateImprintSku( $imprintOption )
	{
		$sku = $imprintOption->Sku;
		$sku .= '_';
		$imprintName = $imprintOption->ImprintTexts->nl->Name;
		$words = explode( ' ', $imprintName );
		$words_short = array_map( function ( $el ) {
			return substr( $el, 0, 2 );
		}, $words );
		$sku .= implode( $words_short, '_' );

		$prices = $this->getProductPrices( $imprintOption );
		$prices_sum = array_reduce( $prices, function ( $sum, $item ) {
			return $sum += $item[ 'price' ][ 0 ][ 'net' ];
		} );

		$sku .= '_';
		$sku .= $prices_sum;
		return $sku;
	}

	public function verbose( $string )
	{
		if ( $this->verbose ) {
			$this->output->writeln( $string . ' mem(' . memory_get_usage() . ')' );
		}
	}

	private function getProductPrices( $product )
	{
		$quantityMap = [];
		$ppcb = $this->getLocalized( $product->ProductPriceCountryBased );
		foreach ( $ppcb->RecommendedSellingPrice as $quantityObj ) {
			$quantityMap[ $quantityObj->Quantity ] = null;
		}
		$quantityKeys = array_keys( $quantityMap );
		$i = 1;
		foreach ( $quantityMap as $k => $v ) {
			if ( $i == array_key_last( $quantityKeys ) + 1 ) {
				continue;
			}
			$quantityMap[ $k ] = $quantityKeys[ $i ] - 1;
			$i++;
		}
		$productPrices = [];

		foreach ( $ppcb->RecommendedSellingPrice as $priceObj ) {
			$productPrices[] = [
				'quantityStart' => $priceObj->Quantity,
				'quantityEnd' => $quantityMap[ $priceObj->Quantity ],
				'ruleId' => $this->ruleId,
				'price' => [
					[
						'currencyId' => Defaults::CURRENCY,
						'gross' => $priceObj->Price * 1.21, // 21% Tax
						'net' => $priceObj->Price,
						'linked' => true
					]
				]
			];
		}

		//This is required in Shopware 6.4;
		$productPrices[ 0 ][ 'quantityStart' ] = 1;

		return $productPrices;
	}

	private function getLocalized( $obj )
	{
		if ( isset( $obj->{'nl'} ) ) {
			return $obj->{'nl'};
		}
		if ( isset( $obj->{'nl-NL'} ) ) {
			return $obj->{'nl-NL'};
		}
		if ( isset( $obj->{'NLD'} ) ) {
			return $obj->{'NLD'};
		}
		if ( isset( $obj->{'NLD'} ) ) {
			return $obj->{'NLD'};
		}
		if ( isset( $obj->{'BENELUX'} ) ) {
			return $obj->{'BENELUX'};
		}
		if ( isset( $obj->{'EURO'} ) ) {
			return $obj->{'EURO'};
		}
		throw new Exception( 'Expected nl/nl-NL/NLD/BENELUX/EURO localization.' );
		return null;
	}

	private function createOrGetMediaId( string $url, $folderEntity = 'product' )
	{
		if ( isset( $this->mediaIds[ $url ] ) ) {
			return $this->mediaIds[ $url ];
		}
		$this->importImageService->setFolderEntity( $folderEntity );
		echo( 'Adding Image to Media From URL...mem(' . memory_get_usage() . ')' . PHP_EOL );
		$mediaId = $this->importImageService->addImageToMediaFromURL( $url, $this->context );
		echo( 'Added.' . PHP_EOL );
		$this->mediaIds[ $url ] = $mediaId;
		return $mediaId;
	}

	private function addProductMedia( string $productId, string $mediaId )
	{
		$productMediaId = Uuid::randomHex();
		$productMediaData = ['id' => $productMediaId, 'productId' => $productId, 'mediaId' => $mediaId];
		$this->productMediaRepository->create( [$productMediaData], $this->context );

		return $productMediaId;
	}

	private function setProductCover( string $productId, string $productMediaId )
	{
		$productData = [
			'id' => $productId,
			'coverId' => $productMediaId
		];
		$this->productRepository->update( [$productData], $this->context );
	}

	private function getTaxId(): string
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'taxRate', 21.00 ) );

		return $this->taxRepository->searchIds( $criteria, $this->context )->firstId();
	}

	private function getDeliveryTimeId(): string
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'min', 10 ) );
		$criteria->addFilter( new EqualsFilter( 'max', 10 ) );

		return $this->deliveryTimeRepository->searchIds( $criteria, $this->context )->firstId();
	}

	private function getRuleId(): string
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'name', 'Always valid (Default)' ) );

		return $this->ruleRepository->searchIds( $criteria, $this->context )->firstId();

	}

	private function getSalesChannelId(): string
	{
		$criteria = ( new Criteria() )
			->addAssociation( 'sales_channel_translation' )
			->addFilter( new EqualsFilter( 'name', 'Storefront' ) );

		return $this->salesChannelRepository->searchIds( $criteria, $this->context )->firstId();
	}

	private function getCategoryId( string $path ): string
	{
		$criteria = ( new Criteria() )
			->addAssociation( 'tags' )
			->addFilter( new EqualsFilter( 'tags.name', $path ) );

		return $this->categoryRepository->searchIds( $criteria, $this->context )->firstId() ?? '';
	}

	private function getCategoryIdByName( string $name )
	{
		$criteria = ( new Criteria() )
//			->addAssociation( 'tags' )
			->addFilter( new EqualsFilter( 'name', $name ) );

		return $this->categoryRepository->searchIds( $criteria, $this->context )->firstId() ?? '';
	}

	private function getPropertyOptionId( string $property, string $option, $colorHex = false ):string
	{
		$criteria = ( new Criteria() )
			->addAssociation( 'property_group_translation' )
			->addFilter( new EqualsFilter( 'name', $property ) );

		$propertyId = $this->propertyGroupRepository->searchIds( $criteria, $this->context )->firstId();
		if ( ! $propertyId ) {
			$propertyId = Uuid::randomHex();
			$propertyData = [
				'id' => $propertyId,
				'name' => $property,
				'sortingType' => PropertyGroupDefinition::SORTING_TYPE_ALPHANUMERIC,
				'displayType' => PropertyGroupDefinition::DISPLAY_TYPE_TEXT
			];
			$this->propertyGroupRepository->create( [$propertyData], $this->context );
		}

		$criteria = ( new Criteria() )
			->addAssociation( 'property_group_option_translation' )
			->addFilter( new EqualsFilter( 'groupId', $propertyId ) )
			->addFilter( new EqualsFilter( 'name', $option ) );
		$optionId = $this->propertyGroupOptionRepository->searchIds( $criteria, $this->context )->firstId();
		if ( ! $optionId ) {
			$optionId = Uuid::randomHex();
			$optionData = [
				'id' => $optionId,
				'groupId' => $propertyId,
// @TODO		'mediaId'
				'name' => $option
			];
			if ( $colorHex ) {
				$optionData[ 'colorHexCode' ] = $colorHex;
			}
			$this->propertyGroupOptionRepository->create( [$optionData], $this->context );
		}

		return $optionId;
	}

	/**
	 * Method, that returns an productNumber of a existing product or NULL if product not exist in db
	 *
	 * @param string $sku
	 * @return string|null
	 */
	private function checkProductExist( $sku )
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'productNumber', $sku ) );

		$productId = $this->productRepository->searchIds( $criteria, $this->context )->firstId();
		if ( $productId ) {
			return $productId;
		}

		return NULL;
	}

	/**
	 * Method, deletes the product_configurator_settings records.
	 *
	 * @param string $productId
	 */
	private function deleteProductConfiguratorSettings( $productId )
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'productId', $productId ) );

		$ids = $this->productConfiguratorSettingRepository->searchIds( $criteria, $this->context )->getIds();
		$ids = array_map( function ( $el ) {
			return ['id' => $el];
		}, $ids );
		try {
			$this->productConfiguratorSettingRepository->delete( $ids, $this->context );
		} catch ( \Exception $e ) {
			echo "ERROR::" . $e->getMessage() . PHP_EOL;
		}
	}

	/**
	 * Method, deletes the cross sellings for product
	 *
	 * @param string $productId
	 */
	private function deleteProductCrossSellings( $productId )
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'productId', $productId ) );

		$crossSellingIds = $this->productCrossSellingRepository->searchIds( $criteria, $this->context )->getIds();
		$crossSellingIds = array_map( function ( $el ) {
			return ['id' => $el];
		}, $crossSellingIds );
		try {
			$this->productCrossSellingRepository->delete( $crossSellingIds, $this->context );
		} catch ( \Exception $e ) {
			echo "ERROR::" . $e->getMessage() . PHP_EOL;
		}
	}

	/**
	 * Method, deletes the cross media for product
	 *
	 * @param string $productId
	 */
	private function deleteProductMedia( $productId )
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'productId', $productId ) );

		$mediaIds = $this->productMediaRepository->searchIds( $criteria, $this->context )->getIds();
		$mediaIds = array_map( function ( $el ) {
			return ['id' => $el];
		}, $mediaIds );
		try {
			$this->productMediaRepository->delete( $mediaIds, $this->context );
		} catch ( \Exception $e ) {
			echo "ERROR::" . $e->getMessage() . PHP_EOL;
		}
	}

	/**
	 * Method, that store product cover image and gallery images
	 *
	 * @param string $productId
	 */
	private function fillProductImages( $productId, $productObject )
	{
		$mediaId = $this->createOrGetMediaId( $productObject->ProductDetails->nl->Image->Url );
		if ( $mediaId ) {
			$childMediaId = $this->addProductMedia( $productId, $mediaId );
			$this->setProductCover( $productId, $childMediaId );
		}

		$gallery = $productObject->ProductDetails->nl->MediaGalleryImages ?? [];

		if ( $gallery ) {
			foreach ( $gallery as $m ) {
				$mediaId = $this->createOrGetMediaId( $m->Url );
				if ( $mediaId ) {
					$this->addProductMedia( $productId, $mediaId );
				}
			}
		}
	}


	/**
	 *  Method, deletes the advanced prices for product
	 *
	 * @param string $productId
	 * @return string|null
	 */

	private function deleteProductPrices( $productId )
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'productId', $productId ) );
		$productPriceIds = $this->productPriceRepository->searchIds( $criteria, $this->context )->getIds();

		$productPriceIds = array_map( function ( $el ) {
			return ['id' => $el];
		}, $productPriceIds );

		try {
			$this->productPriceRepository->delete( $productPriceIds, $this->context );
		} catch ( \Exception $e ) {
			echo "ERROR::" . $e->getMessage() . PHP_EOL;
		}
	}

	private function deleteProductOptions( $productId )
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'productId', $productId ) );

		$ids = $this->productOptionRepository->searchIds( $criteria, $this->context )->getIds();
		$poIds = array_map( function ( $el ) use ( $productId ) {
			return [
				'productId' => $productId,
				'optionId' => $el[ 'optionId' ]
			];
		}, $ids );

		$this->productOptionRepository->delete( $poIds, $this->context );
	}

	private function deleteProductProperties( $productId )
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'productId', $productId ) );

		$ids = $this->productPropertyRepository->searchIds( $criteria, $this->context )->getIds();

		$poIds = array_map( function ( $el ) use ( $productId ) {
			return [
				'productId' => $productId,
				'optionId' => $el[ 'optionId' ]
			];
		}, $ids );

		$this->productPropertyRepository->delete( $poIds, $this->context );
	}

	private function deleteRemovedChildProducts( $productId, $obj )
	{
		$connection = \Shopware\Core\Kernel::getConnection();
		/** @var QueryBuilder $queryBuilder */
		$queryBuilder = $connection->createQueryBuilder();

		$queryBuilder
			->select( 'HEX(id) as id_hex', 'product_number' )
			->from( 'product' )
			->where( 'HEX(parent_id) = ?' )
			->setParameter( 0, $productId );

		$oldChilds = $queryBuilder->execute()->fetchAll();

		$oldSkus = array_map( function ( $el ) {
			return $el[ 'product_number' ];
		}, $oldChilds
		);

		$newSkus = array_map( function ( $el ) {
			return $el->Sku;
		}, $obj->ChildProducts );

		$diffSkus = array_diff( $oldSkus, $newSkus );
		$removedChilds = array_filter( $oldChilds, function ( $el ) use ( $diffSkus ) {
			return in_array( $el[ 'product_number' ], $diffSkus );
		} );
		$removedIds = [];
		foreach ( $removedChilds as $rc ) {
			$removedIds[] = ['id' => $rc[ 'id_hex' ]];
		}

		if ( ! empty( $removedIds ) ) {
			echo "DELETING REMOVED CHILD VARIANTS" . print_r( $removedIds );
			$this->productRepository->delete( $removedIds, $this->context );
		}
	}
}
