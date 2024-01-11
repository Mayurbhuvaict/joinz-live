<?php declare( strict_types = 1 );

namespace JoinzImportPlugin\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\Language;

class ImportCategoryService
{
	protected $categoryRepository;
	protected $languageRepository;
	protected $cmsPageRepository;

	protected $parrentArr = [];
	const EN_INDEX = 2;//NL is 3
	const NL_INDEX = 3;
	const KEY_INDEX = 0;

	public function __construct( EntityRepositoryInterface $categoryRepository,
								 EntityRepositoryInterface $languageRepository,
								 EntityRepositoryInterface $cmsPageRepository
	)
	{
		$this->categoryRepository = $categoryRepository;
		$this->languageRepository = $languageRepository;
		$this->cmsPageRepository = $cmsPageRepository;
	}

	public function import( $categoriesArr, OutputInterface $output )
	{
		$countSuccess = 0;
		$countFixed = 0;

		$context = Context::createDefaultContext();
		$englishId = $this->getEnglishLanguageId( $context );
		$dutchId = $this->getDutchLanguageId( $context );

		$mainCategory = $this->getMainCategory( $context );
		$defaultLayoutCmsPageId = $this->getDefaultLayoutCmsPage( $context );

		foreach ( $categoriesArr as $i => $categoryArr ) {
			$parentId = $mainCategory->getId();
			$versionId = $mainCategory->getVersionId();
//			if ( $i < 2 ) {
//				continue;
//			}
			if ( empty( $categoryArr ) || empty( $categoryArr[ 0 ] ) ) {
				$output->writeln( 'Category is empty key:' . $i );
				continue;
			}

			// NL category name check
			if ( ! isset( $categoryArr[ self::NL_INDEX ] ) ) {
				$output->writeln( 'Wrong data format for record with key: ' . $i );
				continue;
			}

			$catData = $this->getNameAndLevel( $categoryArr );

			// Search for parent id and version id in parents array
			if ( $parentKey = $catData[ 'parent_key' ] ) {
				$parentId = $this->parrentArr[ $parentKey ][ 'category_id' ] ?? null;
				$versionId = $this->parrentArr[ $parentKey ][ 'category_version_id' ] ?? null;
			}
			$categoryId = Uuid::randomHex();

			$categoryData = [
				'id' => $categoryId,
				'level' => $catData[ 'level' ],
				'name' => $catData[ 'name_en' ],
				'parentId' => $parentId,
				'parent_version_id' => $versionId,
				'cmsPageId' => $defaultLayoutCmsPageId,
				'translations' => [
					['languageId' => $englishId, 'name' => $catData[ 'name_en' ]],
					['languageId' => $dutchId, 'name' => $catData[ 'name_dutch' ]]
				],
				'tags' => [
					['name' => $catData[ 'path' ]]
				]

			];

			$categoryRecord = $this->categoryRepository->create( [$categoryData], $context );
			$categoryVersionId = $categoryRecord->getContext()->getVersionId();
			//$categoryLangId = $categoryRecord->getContext()->getLanguageId();

			// Fill parents map array for name->category id | category version id
			$this->parrentArr[ $catData[ 'key' ] ] = ['category_id' => $categoryId, 'category_version_id' => $categoryVersionId];

			$output->writeln( 'Successfully inserted category with key: ' . $categoryArr[ self::KEY_INDEX ] );
			$countSuccess++;
		}
		$output->writeln( 'Done! Successfully imported ' . $countSuccess . ' categories.' );
		$output->writeln( 'Successfully fixed records: ' . $countFixed );
	}

	private function getNameAndLevel( array $category ) : array
	{
		$categoriesKeys = explode( '/', $category[ static::KEY_INDEX ] );
		$categoriesNamesEn = explode( '/', $category[ static::EN_INDEX ] );
		$categoriesNamesDutch = explode( '/', $category[ static::NL_INDEX ] );
		$res = [];
		$level = count( $categoriesKeys );
		$parent_index = $level - 2;
		$parentName = $categoriesNamesEn[ $parent_index ] ?? null;
		$parentKey = $categoriesKeys[ $parent_index ] ?? null;

		$res[ 'name_en' ] = end( $categoriesNamesEn );
		$res[ 'name_dutch' ] = end( $categoriesNamesDutch );
		$res[ 'key' ] = end( $categoriesKeys );
		$res[ 'level' ] = $level;
		$res[ 'parent_name' ] = $parentName;
		$res[ 'parent_key' ] = $parentKey;
		$res[ 'path' ] = $category[ static::KEY_INDEX ];

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

	private function getEnglishLanguageId( Context $context ) : string
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'name', 'English' ) );

		return $this->languageRepository->searchIds( $criteria, $context )->firstId();
	}

	private function getDutchLanguageId( Context $context ) : string
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'name', 'Dutch' ) );

		return $this->languageRepository->searchIds( $criteria, $context )->firstId();
	}
}
