<?php

namespace JoinzImportPlugin\Command;

use Exception;
use JoinzImportPlugin\Service\ImportImage;
use JoinzImportPlugin\Service\ImportService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCommand extends Command
{
	const ARG_NAME = 'limit';
	const OPT_VERBOSE = 'verbose';
	const OPT_SKU = 'sku';

	protected static $defaultName = 'joinz:import';

	protected $importService;
	protected $importImage;
	protected $productHashRepository;

	protected $errors = 0;

	public function __construct( ImportService $importService, EntityRepositoryInterface $productHashRepository )
	{
		$this->importService = $importService;
		$this->productHashRepository = $productHashRepository;
		parent::__construct();
	}

	protected function configure()
	{
		$this->addArgument( self::ARG_NAME, InputArgument::OPTIONAL, 'This is an optional argument.' );
		$this->addOption( self::OPT_SKU, 's', InputOption::VALUE_OPTIONAL, 'Verbose output option.', false );
		//		$this->addOption( self::OPT_VERBOSE, 'v', InputOption::VALUE_OPTIONAL, 'Verbose output option.', false );
	}

	protected function execute( InputInterface $input, OutputInterface $output ) : int
	{
		//get the arguments
		$arguments = $input->getArguments();
		$options = $input->getOptions();
		$verbose = $input->getOption( 'verbose' );

//		$dir = getcwd() . '/custom/static-plugins/JoinzImportPlugin/src/Resources/json_files/';
//		$json_string = file_get_contents( $dir . 'A36-MO1059.json' );
//		$this->importService->import( $json_string, $output, $withImages = true, $hash = false, $verbose );
//		die;

		if ( $verbose ) {
			$output->writeln( 'Fetching feed data..' );
		}
		$feedData = file( 'https://promidatabase.s3.eu-central-1.amazonaws.com/Profiles/Live/020700ae-8af7-453d-950a-198bf29c8f84/Import/Import.txt?fbclid=IwAR0uMftFaSuDy9TLS24554jLgXO8BlfWhARYAu37LcSVo_afoRzwQagH1Aw' );
		$feedData = array_slice( $feedData, 1 ); //skip category row


		if ( $options[ 'sku' ] ) {
			$needleSku = $options[ 'sku' ].'.json';

			$resElement = array_filter( $feedData, function ( $haystack ) use ( $needleSku ) {
				return ( strpos( $haystack, $needleSku ) );
			} );

			if ( empty( $resElement ) ) {
				$output->writeln( 'Cannot find product with sku: ' . $needleSku );
			} elseif ( sizeof( $resElement ) > 1 ) {
				$output->writeln( 'Found more then one products with sku: ' . $needleSku );
				$output->writeln( 'Aborting..' );
			} else {
				$this->importJsonContent( reset( $resElement ), $output, $verbose );
				$output->writeln( 'Successfully imported product with sku: ' . $needleSku );
			}

			return 1;
		}

		$count = 0;
		if ( $arguments[ 'limit' ] ) {
			$limit = (int)$arguments[ 'limit' ];
			$output->writeln( 'Getting first ' . $limit . ' records.' );
		}

		$output->writeln( 'Importing...' );

		$max_mem = 5000100100;//5.0GB
		foreach ( $feedData as $element ) {
			if ( memory_get_usage() > $max_mem ) {
				$output->writeln( 'Memory ' . memory_get_usage() . ' is above ' . $max_mem . '.Quitting.' );
				break;
			}
			if ( $count % 1 == 0 ) {
				$output->writeLn( 'IMPORTED:IMPORTED:IMPORTED:IMPORTED:IMPORTED:IMPORTED: ' . $count );
			}
			$count++;

			$this->importJsonContent( $element, $output, $verbose );

			if ( $arguments[ 'limit' ] && $count >= $limit ) {
				break;
			}
		}

		$output->writeln( 'Successfully imported ' . $count . ' records.' );
		$output->writeln( 'Errors (' . $this->errors . ')' );

		//$this->importService->import($json_string);

		//return success code
		return 0;
	}

	private function importJsonContent( $element, OutputInterface $output, $verbose = false )
	{
		$json_link = substr( $element, 0, strpos( $element, "|" ) );
		$hash = trim( substr( $element, strpos( $element, "|" ) + 1 ) );
		$sku = basename( $json_link, '.json' );

		try {
			//@TODO MOVE HASH HERE AND DON'T FETCH IF IT'S ALREADY IMPORTED
			$context = Context::createDefaultContext();

			if ( ! $hash ) {
				$output->writeln( "There is no hash for product: " . $sku );
			} else {
				$hashEntity = $this->searchProductHashBySku( $context, $sku );
				if ( $hashEntity && $hashEntity->getProductHash() == $hash ) {
					$output->writeln( "Already exist product with hash: " . $hash );
					return;
				} else {
					if ( $hashEntity ) {
						$output->writeln( sprintf( "Hash differs: (%s) (%s)", $hashEntity->getProductHash(), $hash ) );
					} else {
						$output->writeln( "Hash is not found: " . $hash );
					}
				}
			}

			if ( $verbose ) {
				$output->writeln( 'Fetching ' . $json_link );
			}
			try {
				$json_string = file_get_contents( $json_link );
				if ( $verbose ) {
					$output->writeln( 'Fetching done.' );
				}
			} catch ( Exception $e ) {
				$output->writeln( 'ERROR ' . $e->getMessage() );
				$output->writeln( ' Sleeping for 60 seconds...' );
				sleep( 60 );
				$json_string = file_get_contents( $json_link );
				if ( $verbose ) {
					$output->writeln( 'Fetching done.' );
				}
			}


			$this->importService->import( $json_string, $output, $withImages = true, rtrim( $hash ), $verbose, $hashEntity );
		} catch ( Exception $e ) {
			$this->errors++;
			$output->writeln( 'ERROR: ' . $e->getMessage() );
		}
	}

	/**
	 * Method, that search for hash record by sku and returns record id or NULL
	 *
	 * @param string $sku
	 * @param Context $context
	 * @return string|null
	 */
	private function searchProductHashBySku( Context $context, $sku )
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'productNumber', $sku ) );

		$productHash = $this->productHashRepository->search( $criteria, $context )->first();
		return $productHash;
	}

	/**
	 * Method, that checks for already existing hash of product and returns true/false
	 *
	 * @param string $hash
	 * @param Context $context
	 * @return bool
	 */
	private function hashExist( Context $context, $hash ) : bool
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'productHash', $hash ) );

		$hash = $this->productHashRepository->search( $criteria, $context )->first();
		if ( $hash ) {
			return true;
		}

		return false;
	}
}
