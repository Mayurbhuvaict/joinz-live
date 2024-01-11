<?php

namespace JoinzImportPlugin\Command;

use JoinzImportPlugin\Service\ImportCategoryFileService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCategoryFileCommand extends Command
{

	protected static $defaultName = 'joinz:import-categories-file';

	protected $importCategoryFileService;
	protected $mapArr = [];

	public function __construct( ImportCategoryFileService $importCategoryFileService )
	{
		$this->importCategoryFileService = $importCategoryFileService;

		parent::__construct();
	}

	protected function configure()
	{

	}

	protected function execute( InputInterface $input, OutputInterface $output ) : int
	{
		$verbose = $input->getOption( 'verbose' );
		$output->writeln( 'Fetching file data..' );
		$dir = getcwd() . '/custom/static-plugins/JoinzImportPlugin/src/Resources/csv_files/';
		$f = fopen( $dir . 'Category upload.xlsx inclusief metabeschrijving.xlsx 2.xlsx - Upload Categories.csv', 'r' );
		$csv = [];
		while ( ( $row = fgetcsv( $f, 10000, "," ) ) !== FALSE ) {
			$csv[] = $row;
		}
		unset( $csv[ 0 ] );
		$this->importCategoryFileService->import( $csv, $output, $verbose );
		fclose( $f );
	}
}
