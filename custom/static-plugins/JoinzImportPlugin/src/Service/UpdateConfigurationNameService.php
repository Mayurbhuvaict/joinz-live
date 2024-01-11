<?php

namespace JoinzImportPlugin\Service;

use Exception;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateConfigurationNameService
{
	protected $productRepository;
	protected $propertyGroupRepository;
	protected $alreadyUpdated = ['color','Color'];

	public function __construct( EntityRepositoryInterface $productRepository, EntityRepositoryInterface $propertyGroupRepository )
	{
		$this->productRepository = $productRepository;
		$this->propertyGroupRepository = $propertyGroupRepository;
	}

	public function update( $json_string, OutputInterface $output, $verbose = false )
	{
		try {
			$this->verbose( $output, $verbose, '========================BEGIN======================' );
			$obj = json_decode( $json_string );

			$context = Context::createDefaultContext();

			foreach ( $obj->ChildProducts as $childProduct ) {
				if ( ! isset( $childProduct->ProductDetails->nl ) ) {
					$this->verbose( $output, $verbose, 'ChildProduct doesn\'t have ProductDetails->nl' );
					continue;
				}

				$configurationFields = $childProduct->ProductDetails->nl->ConfigurationFields;
				foreach ( $configurationFields as $configField ) {
					$configName = $configField->ConfigurationName;

					if ( in_array( $configName, $this->alreadyUpdated ) ) {
						$this->verbose( $output, $verbose, 'Already updated property name: ' . $configName . '. Skipping..' );
						continue;
					}

					$this->verbose( $output, $verbose, 'Getting Property Id' );
					$isUpdated = $this->updatePropertyName( $configName, $context, $configField->ConfigurationNameTranslated );
					if ( $isUpdated ) {
						$this->alreadyUpdated [] = $configName;
						$this->verbose( $output, $verbose, 'Successfully updated property with name: ' . $configName );
					} else {
						$this->verbose( $output, $verbose, 'Warning cannot find property with name: ' . $configName );
					}
				}
			}
			$this->verbose( $output, $verbose, '========================END======================' );
		} catch ( Exception $e ) {
			throw $e;
		}
	}

	public function verbose( $output, $verbose, $string )
	{
		if ( $verbose ) {
			$output->writeln( $string . ' mem(' . memory_get_usage() . ')' );
		}
	}

	private function updatePropertyName( string $property, Context $context, string $propertyTranslated ) : string
	{
		$criteria = ( new Criteria() )
			->addAssociation( 'property_group_translation' )
			->addFilter( new EqualsFilter( 'name', $property ) );

		$propertyId = $this->propertyGroupRepository->searchIds( $criteria, $context )->firstId();
		if ( ! $propertyId ) {
			return false;
		} else {
			$propertyData = [
				'id' => $propertyId,
				'name' => $propertyTranslated
			];
			$this->propertyGroupRepository->update( [ $propertyData ], $context );
		}

		return true;
	}
}
