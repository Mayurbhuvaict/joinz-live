<?php declare( strict_types = 1 );

namespace JoinzImportPlugin\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\Language;

class ImportCategoryFileService
{
	protected $categoryRepository;
	protected $languageRepository;
	protected $cmsPageRepository;

	protected $parrentArr = [];
	const NL_INDEX = 0;
	const PARENT_INDEX = 1;
	const KEY_INDEX = 0;
	const SHORT_DESC = 3;
	const META_TITLE = 4;
	const META_DESC = 5;
	const CUSTOM_TITLE = 2;

	public function __construct( EntityRepositoryInterface $categoryRepository,
								 EntityRepositoryInterface $languageRepository,
								 EntityRepositoryInterface $cmsPageRepository
	)
	{
		$this->categoryRepository = $categoryRepository;
		$this->languageRepository = $languageRepository;
		$this->cmsPageRepository = $cmsPageRepository;
	}

	public function import( $csv, OutputInterface $output, $verbose = false )
	{

		$countSuccess = 0;
		$countFixed = 0;

		$context = Context::createDefaultContext();
		$dutchId = $this->getDutchLanguageId( $context );

		$mainCategory = $this->getMainCategory( $context );
		$defaultLayoutCmsPageId = $this->getDefaultLayoutCmsPage( $context );

//		dd($csv);
		foreach ( $csv as $i => $categoryArr ) {

			if ( empty( $categoryArr ) || empty( $categoryArr[ static::NL_INDEX ] ) ) {
				$output->writeln( 'Category with key:' . $i . ' is empty.' );
				continue;
			}

			$catData = $this->getNameAndLevel( $categoryArr );


			$parentName = $categoryArr[ self::PARENT_INDEX ];

			if ( ! $parentName ) {
				$parent = $mainCategory;
			} else {
				$parent = static::getCategory( $context, $parentName );
			}
			if ( ! $parent ) {
				$output->writeln( 'Skipping no parent: ' . $parentName );
				// Try next run;
				continue;
			}
			$parentId = $parent->getId();
			$versionId = $parent->getVersionId();

			$category = static::getCategory( $context, $catData[ 'name_dutch' ] );

			if ( $category ) {
				$output->writeln( 'Category already exists: ' . $catData[ 'name_dutch' ] );

				$this->categoryRepository->update( [[
						'id' =>  $category->getId(),
						'customFields' => [
						'custom_category_info_title' => $categoryArr[ static::CUSTOM_TITLE ] ?? null,
					]
				]], $context );

				continue;
			}

			$categoryId = Uuid::randomHex();

			$categoryData = [
				'id' => $categoryId,
//                'level' => $catData[ 'level' ],
				'name' => $catData[ 'name_dutch' ],
				'parentId' => $parentId ?? null,
				'parent_version_id' => $versionId,
				'cmsPageId' => $defaultLayoutCmsPageId,
				'translations' => [
					[
						'languageId' => $dutchId,
						'name' => $catData[ 'name_dutch' ],
						'metaTitle' => $categoryArr[ static::META_TITLE ] ?? null,
						'metaDescription' => $categoryArr[ static::META_DESC ] ?? null,
						'description' => $categoryArr[ static::SHORT_DESC ] ?? null
					]
				],
				'tags' => [
					// ['name' => $catData[ 'path' ]]
				],
				'customFields' => [
					'custom_category_info_title' => $categoryArr[ static::CUSTOM_TITLE ] ?? null,
				]
			];


			$this->categoryRepository->create( [$categoryData], $context );
			$output->writeln( 'Successfully inserted category with key: ' . $categoryArr[ self::KEY_INDEX ] );

			$countSuccess++;
		}
		$output->writeln( 'Done! Successfully imported ' . $countSuccess . ' categories.' );
		$output->writeln( 'Successfully fixed records: ' . $countFixed );
	}

	private function getNameAndLevel( array $category ) : array
	{
//        $categoriesNamesEn = explode( '/', $category[ static::NL_INDEX ] );
		$res = [];
//        $level = count( $categoriesNamesEn );
		$parentName = $category[ static::PARENT_INDEX ] ?? null;

		$res[ 'name_dutch' ] = $category[ static::NL_INDEX ];
//        $res[ 'level' ] = $level;
		$res[ 'parent_name' ] = $parentName;
		$res[ 'parent_key' ] = $parentName ?? null;

		return $res;
	}

	private function getMainCategory( Context $context )
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'name', 'Catalogue #1' ) );

		$categories = $this->categoryRepository->search( $criteria, $context );
		return $categories->first();
	}

	private function getDefaultLayoutCmsPage( Context $context )
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'name', 'Default category layout2' ) );

		return $this->cmsPageRepository->searchIds( $criteria, $context )->firstId();
	}

	private function getDutchLanguageId( Context $context )
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'name', 'Dutch' ) );

		return $this->languageRepository->searchIds( $criteria, $context )->firstId();
	}

	private function getCategory( Context $context, $categoryName )
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'name', $categoryName ) );

		$category = $this->categoryRepository->search( $criteria, $context )->first();
		return $category;
	}
}
