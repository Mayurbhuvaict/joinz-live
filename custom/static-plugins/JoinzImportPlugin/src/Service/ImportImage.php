<?php declare( strict_types = 1 );

namespace JoinzImportPlugin\Service;

use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Content\Media\Exception\DuplicatedMediaFileNameException;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class ImportImage
{
	const TEMP_NAME = 'image-import-from-url';      //prefix for temporary files, downloaded from URL
	const MEDIA_DIR = '/public/media/';             //relative path to Shopware's media directory
	private $mediaFolder = 'product';           //name of the folder in Shopware's media data structure

	private $mediaRepository;
	private $mediaService;
	private $fileSaver;

	/**
	 * ImageImport constructor.
	 *
	 * @param EntityRepositoryInterface $mediaRepository
	 * @param MediaService $mediaService
	 * @param FileSaver $fileSaver
	 */
	public function __construct(
		EntityRepositoryInterface $mediaRepository,
		MediaService $mediaService,
		FileSaver $fileSaver
	)
	{
		$this->mediaRepository = $mediaRepository;
		$this->mediaService = $mediaService;
		$this->fileSaver = $fileSaver;
	}

	public function setFolderEntity( string $folderEntity )
	{
		// $folderEntity = product or product_cross_selling
		$this->mediaFolder = $folderEntity;
	}

	/**
	 * Method, that downloads a file from a URL and returns an ID of a newly created media, based on it
	 *
	 * @param string $imageUrl
	 * @param Context $context
	 * @return string|null
	 */
	public function addImageToMediaFromURL( string $imageUrl, Context $context )
	{
		$mediaId = null;

		//parse the URL
		$filePathParts = explode( '/', $imageUrl );
		$fileNameParts = explode( '.', array_pop( $filePathParts ) );

		//get the file name and extension
		$fileName = $fileNameParts[ 0 ];
		$fileExtension = $fileNameParts[ 1 ];

		if ( $fileName && $fileExtension ) {
			$mediaId = $this->checkImageExist( $context, $fileName );
			if ( ! $mediaId ) {
				echo "===========DOWNLOADING FILE...==================" . PHP_EOL;
				//copy the file from the URL to the newly created local temporary file
				$filePath = tempnam( sys_get_temp_dir(), self::TEMP_NAME );
				file_put_contents( $filePath, file_get_contents( $imageUrl ) );
				echo "Downloaded." . PHP_EOL;

				echo "===========CREATING MEDIA FROM FILE...==================" . PHP_EOL;
				//create media record from the image
				$mediaId = $this->createMediaFromFile( $filePath, $fileName, $fileExtension, $context );
				echo "Created." . PHP_EOL;
			}
		}

		return $mediaId;
	}

	/**
	 * Method, that returns an ID of a existing media or NULL if media not exist in db
	 *
	 * @param string $fileName
	 * @param Context $context
	 * @return string|null
	 */
	private function checkImageExist( Context $context, $fileName )
	{
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'fileName', $fileName ) );

		$imageId = $this->mediaRepository->searchIds( $criteria, $context )->firstId();
		return $imageId;
	}

	/**
	 * Method, that returns an ID of a newly created media, based on a local file from the Shopware's media directory
	 *
	 * @param string $fileName
	 * @param string $directoryName
	 * @param Context $context
	 * @return string|null
	 */
	public function addImageToMediaFromFile( string $fileName, string $directoryName, Context $context )
	{
		//compose the path to file
		$filePath = dirname( __DIR__, 5 ) . self::MEDIA_DIR . $directoryName . '/' . $fileName;

		//get the file extension
		$fileNameParts = explode( '.', $fileName );
		$fileExtension = $fileNameParts[ 1 ];

		//create media record from the image and return its ID
		return $this->createMediaFromFile( $filePath, $fileName, $fileExtension, $context );
	}


	/**
	 * Method, that creates a new media record from a local file and returns its ID
	 *
	 * @param string $filePath
	 * @param string $fileName
	 * @param string $fileExtension
	 * @param Context $context
	 * @return string|null
	 */
	private function createMediaFromFile( string $filePath, string $fileName, string $fileExtension, Context $context )
	{
		$mediaId = null;

		//get additional info on the file
		$fileSize = filesize( $filePath );
		$mimeType = mime_content_type( $filePath );

		//create and save new media file to the Shopware's media library
		try {
			$mediaFile = new MediaFile( $filePath, $mimeType, $fileExtension, $fileSize );
			$mediaId = $this->mediaService->createMediaInFolder( $this->mediaFolder, $context, false );
			$this->fileSaver->persistFileToMedia(
				$mediaFile,
				$fileName,
				$mediaId,
				$context
			);
		} catch ( DuplicatedMediaFileNameException $e ) {
			echo( $e->getMessage() );
			$mediaId = $this->mediaCleanup( $mediaId, $context );
		} catch ( \Exception $e ) {
			echo( $e->getMessage() );
			$mediaId = $this->mediaCleanup( $mediaId, $context );
		}

		return $mediaId;
	}

	/**
	 * Method, that takes care of deleting the newly created media record, if something goes wrong with saving data to it
	 *
	 * @param string $mediaId
	 * @param Context $context
	 * @return null
	 */
	private function mediaCleanup( string $mediaId, Context $context )
	{
		$this->mediaRepository->delete( [['id' => $mediaId]], $context );
		return null;
	}


}
