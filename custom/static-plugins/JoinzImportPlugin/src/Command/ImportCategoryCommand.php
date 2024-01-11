<?php

namespace JoinzImportPlugin\Command;

use JoinzImportPlugin\Service\ImportCategoryService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportCategoryCommand extends Command
{
    const ARG_NAME = 'limit';
    const OPT_NAME = 'option';

    protected static $defaultName = 'joinz:import-categories';

    protected $importCategoryService;
    protected $mapArr = [];

    public function __construct( ImportCategoryService $importCategoryService )
    {
        $this->importCategoryService = $importCategoryService;

        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument( self::ARG_NAME, InputArgument::OPTIONAL, 'This is an optional argument.' );
        $this->addOption( self::OPT_NAME, null, InputOption::VALUE_OPTIONAL, 'This is an optional option.' );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) : int
    {
        //get the arguments
        $arguments = $input->getArguments();

        $output->writeln( 'Fetching feed data..' );
        $feedData = file( 'https://promidatabase.s3.eu-central-1.amazonaws.com/Profiles/Live/020700ae-8af7-453d-950a-198bf29c8f84/Import/Import.txt?fbclid=IwAR0uMftFaSuDy9TLS24554jLgXO8BlfWhARYAu37LcSVo_afoRzwQagH1Aw' );
        $categoriesLink = $feedData[ 0 ];

        $curl = curl_init();
        curl_setopt( $curl, CURLOPT_URL, trim( $categoriesLink ) );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_HEADER, false );
        $categoriesData = curl_exec( $curl );
        curl_close( $curl );

        $lines = explode( PHP_EOL, $categoriesData );
        $arrayCategories = [];
        foreach ( $lines as $line ) {
            $arrayCategories[] = str_getcsv( $line, ';' );
        }
        $arrayCategories = array_slice( $arrayCategories, 1 ); //skip headers row
        $output->writeln( 'Done!' );

        $count = 0;
        if ( $arguments[ 'limit' ] ) {
            $limit = (int)$arguments[ 'limit' ];
            $output->writeln( 'Getting first ' . $limit . ' categories.' );
        }

        $output->writeln( 'Importing categories...' );

      /*  foreach ( $arrayCategories as $category ) {
            $count++;
            if ( $arguments[ 'limit' ] && $count >= $limit ) {
                break;
            }

            $this->importCategoryService->import( $category, $output );
        }*/
        $this->importCategoryService->import( $arrayCategories, $output );

     //   $output->writeln( 'Done! Successfully imported ' . $count . ' categories.' );

        //return success code
        return 1;
    }
}
