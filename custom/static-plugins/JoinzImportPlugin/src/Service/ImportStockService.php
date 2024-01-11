<?php declare( strict_types=1 );

namespace JoinzImportPlugin\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Output\OutputInterface;

class ImportStockService
{
    protected $productRepository;
    protected $productStockRepository;

    public function __construct( EntityRepositoryInterface $productRepository,
                                 EntityRepositoryInterface $productStockRepository)
    {
        $this->productRepository = $productRepository;
        $this->productStockRepository = $productStockRepository;
    }

    public function import( OutputInterface $output, $stockLink, $limit = null )
    {
        $countSuccess = 0;
        $countNotFound = 0;

        $criteria = ( new Criteria() )->setLimit($limit);
        $context = Context::createDefaultContext();

        $output->writeln( 'Getting products...' );
        $products = $this->productRepository->search( $criteria, $context )->getElements();

        $output->writeln( 'Found: ' . count( $products ) );

        foreach ( $products as $product ) {
            $stockData = $this->getStockData( $stockLink, $product->getProductNumber() );
            if (!$stockData && $product->childCount > 0) {
                $criteria = new Criteria();
                $criteria->addFilter(new EqualsFilter('product.parentId',
                    $product->id));
                $childProducts = $this->productRepository->search($criteria, $context);
                $childStock = [];
                foreach ($childProducts as $child) {
                    $childStockData = $this->getStockData( $stockLink, $child->getProductNumber() );
                    if ( ! $childStockData ) {
                        continue;
                    }
                    $childStock[] = $childStockData;
                }
                if (!empty($childStock)) {
                   $stockData = max($childStock);
                }
            }
            if ( ! $stockData ) {
                $output->writeln( 'Cannot find stock info for product:  ' . $product->getId() );
                $countNotFound++;
                continue;
            }

            $stockAmount = (int)$stockData[ 'OnStock' ];
            $this->productRepository->update( [ [
                'id' => $product->getId(),
                'stock' => $stockAmount
            ] ], $context );
            $this->productStockRepository->create( [[
                'id' => Uuid::randomHex(),
                'productNumber' => $product->productNumber,
                'productStock' => $stockAmount,
                'productId' => $product->getId(),
            ]], $context );

            $output->writeln( 'Updated stock info for product: ' . $product->getId() . ' amount in stock: ' . $stockAmount );
            $countSuccess++;
        }

        $output->writeln( 'Done! Successfully updated ' . $countSuccess );
        $output->writeln( 'Cannot found stock data for ' . $countNotFound . ' records: ' );
    }


    private function getStockData( $stockLink, $productNumber )
    {
        $curl = curl_init();
        curl_setopt( $curl, CURLOPT_URL, trim( $stockLink . $productNumber) );
        curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $curl, CURLOPT_HEADER, false );
        $stockData = curl_exec( $curl );
        curl_close( $curl );

        return json_decode( $stockData, true );
    }
}
