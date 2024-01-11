<?php

namespace JoinzImportPlugin\Command;

use JoinzImportPlugin\Service\UpdateConfigurationNameService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateConfigurationNameCommand extends Command
{
	const ARG_NAME = 'limit';
	const OPT_VERBOSE = 'verbose';

	protected static $defaultName = 'joinz:update-configuration-name';

	protected $updateConfigurationNameService;

	protected $errors = 0;

	public function __construct( UpdateConfigurationNameService $updateConfigurationNameService )
	{
		$this->updateConfigurationNameService = $updateConfigurationNameService;
		parent::__construct();
	}

	protected function configure()
	{
		$this->addArgument( self::ARG_NAME, InputArgument::OPTIONAL, 'This is an optional argument.' );
		//		$this->addOption( self::OPT_VERBOSE, 'v', InputOption::VALUE_OPTIONAL, 'Verbose output option.', false );
	}

	protected function execute( InputInterface $input, OutputInterface $output ) : int
	{
		//get the arguments
		$arguments = $input->getArguments();
		$verbose = $input->getOption( 'verbose' );

		if ( $verbose ) {
			$output->writeln( 'Fetching feed data..' );
		}

		$feedData = file( 'https://promidatabase.s3.eu-central-1.amazonaws.com/Profiles/Live/020700ae-8af7-453d-950a-198bf29c8f84/Import/Import.txt?fbclid=IwAR0uMftFaSuDy9TLS24554jLgXO8BlfWhARYAu37LcSVo_afoRzwQagH1Aw' );
		$feedData = array_slice( $feedData, 1 ); //skip category row

		$count = 0;
		if ( $arguments[ 'limit' ] ) {
			$limit = (int)$arguments[ 'limit' ];
			$output->writeln( 'Getting first ' . $limit . ' records.' );
		}

		$output->writeln( 'Updating...' );

		$max_mem = 4000100100;//4.0GB
		foreach ( $feedData as $element ) {
			if ( memory_get_usage() > $max_mem ) {
				$output->writeln( 'Memory ' . memory_get_usage() . ' is above ' . $max_mem . '.Quitting.' );
				break;
			}
			if ( $count % 1 == 0 ) {
				$output->writeLn( 'UPDATED: ' . $count );
			}
			$count++;

			$this->importJsonContent( $element, $output, $verbose );

			if ( $arguments[ 'limit' ] && $count >= $limit ) {
				break;
			}
		}

		$output->writeln( 'Successfully updated ' . $count . ' records.' );
		$output->writeln( 'Errors (' . $this->errors . ')' );

		return 1;
	}

	private function importJsonContent( $element, OutputInterface $output, $verbose = false )
	{
		$json_link = substr( $element, 0, strpos( $element, "|" ) );
		if ( $verbose ) {
			$output->writeln( 'Fetching ' . $json_link );
		}
		try {
			$json_string = file_get_contents( $json_link );
		} catch ( \Exception $e ) {
			$output->writeln( 'ERROR: TRYING AGAIN IN 60 SECONDS' );
			sleep( 60 );
			$json_string = file_get_contents( $json_link );
		}
		if ( $verbose ) {
			$output->writeln( 'Fetching done.' );
		}

		try {
			$this->updateConfigurationNameService->update( $json_string, $output, $verbose );
		} catch ( Exception $e ) {
			$this->errors++;
			$output->writeln( 'ERROR: ' . $e->getMessage() );
		}
	}
}
