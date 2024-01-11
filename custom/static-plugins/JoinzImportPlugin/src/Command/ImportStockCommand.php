<?php

namespace JoinzImportPlugin\Command;

use JoinzImportPlugin\Service\ImportStockService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ImportStockCommand extends Command
{
    const ARG_NAME = 'limit';
    const OPT_NAME = 'option';
    const STOCK_FEED_LINK = "https://stock.promidata.com/stockinfo/";

    protected static $defaultName = 'joinz:import-stock';

    protected $importStockService;
    protected $mapArr = [];

    public function __construct( ImportStockService $importStockService )
    {
        $this->importStockService = $importStockService;

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
        $limit = NULL;

        if ( $arguments[ 'limit' ] ) {
            $limit = (int)$arguments[ 'limit' ];
            $output->writeln( 'Getting first ' . $limit . ' products.' );
        }

        $this->importStockService->import( $output, self::STOCK_FEED_LINK, $limit );

        //return success code
        return 0;
    }
}
