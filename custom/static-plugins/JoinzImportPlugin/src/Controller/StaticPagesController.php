<?php declare( strict_types=1 );

namespace JoinzImportPlugin\Controller;

use JoinzImportPlugin\Service\ImportImage;
use JoinzImportPlugin\Storefront\Page\Joinz\PageLoader;
use Shopware\Core\Content\Mail\Service\MailService;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Content\Media\File\FileNameProvider;
use Shopware\Core\Framework\Util\Random;

/**
 * @RouteScope(scopes={"storefront"})
 */
class StaticPagesController extends StorefrontController
{
	private PageLoader $pageLoader;
	private $mediaUpdater;
	private $fileNameProvider;
	private $mediaService;
	private $logoUploadRepository;
	private $emailService;
	protected $salesChannelRepository;

	public function __construct( FileSaver                 $mediaUpdater,
	                             FileNameProvider          $fileNameProvider,
	                             MediaService              $mediaService,
	                             EntityRepositoryInterface $logoUploadRepository,
	                             MailService               $emailService,
	                             EntityRepositoryInterface $salesChannelRepository,
	                             PageLoader                $pageLoader
	)
	{
		$this->mediaUpdater = $mediaUpdater;
		$this->fileNameProvider = $fileNameProvider;
		$this->mediaService = $mediaService;
		$this->logoUploadRepository = $logoUploadRepository;
		$this->emailService = $emailService;
		$this->salesChannelRepository = $salesChannelRepository;
		$this->pageLoader = $pageLoader;
	}

	/**
	 * @Route("/joinz/about-us", name="frontend.joinz.about-us", methods={"GET"})
	 */
	public function aboutUs( Request $request, SalesChannelContext $context ) : Response
	{
		$page = $this->pageLoader->load( $request, $context );

		return $this->renderStorefront( '/storefront/static/about-us.html.twig', [
			'page' => $page
		] );
	}

	/**
	 * @Route("/joinz/faq", name="frontend.joinz.faq", methods={"GET"})
	 */
	public function faq( Request $request, SalesChannelContext $context ) : Response
	{
		$page = $this->pageLoader->load( $request, $context );

		return $this->renderStorefront( '/storefront/static/faq.html.twig', [
			'page' => $page
		] );
	}

    /**
     * @Route("/joinz/verzending", name="frontend.joinz.verzending", methods={"GET"})
     */
    public function verzending( Request $request, SalesChannelContext $context ) : Response
    {
        $page = $this->pageLoader->load( $request, $context );

        return $this->renderStorefront( '/storefront/static/verzending.html.twig', [
            'page' => $page
        ] );
    }

	/**
	 * @Route("/joinz/upload-logo", name="frontend.joinz.upload-logo", methods={"GET"})
	 */
	public function uploadLogo( Request $request, SalesChannelContext $context ) : Response
	{
		$page = $this->pageLoader->load( $request, $context );

		return $this->renderStorefront( '/storefront/static/upload-logo.html.twig', [
			'page' => $page
		] );
	}

	/**
	 * @Route("/joinz/contact", name="frontend.joinz.contact", methods={"GET"})
	 */
	public function contact( Request $request, SalesChannelContext $context ) : Response
	{
		$page = $this->pageLoader->load( $request, $context );

		return $this->renderStorefront( '/storefront/static/contact.html.twig', [
			'page' => $page
		] );
	}

	/**
	 * @Route("/joinz/order-process", name="frontend.joinz.orders-process", methods={"GET"})
	 */
	public function orders( Request $request, SalesChannelContext $context ) : Response
	{
		$page = $this->pageLoader->load( $request, $context );

		return $this->renderStorefront( '/storefront/static/order-process.html.twig', [
			'page' => $page
		] );
	}

	/**
	 * @Route("/joinz/upload-logo-submit", name="frontend.joinz.upload-logo-submit", methods={"POST"})
	 */
	public function uploadLogoSubmit( Request $request, SalesChannelContext $context )
	{
		$page = $this->pageLoader->load( $request, $context );
		$data = $request->files;
		$testSupportedExtension = array( 'gif', 'png', 'jpg', 'jpeg', 'pdf' );

		$context = Context::createDefaultContext();
		$folder = 'logo_uploads';
		foreach ( $data as $file ) {

			if ( ! $file ) {
				$this->uploadComment( $request, $context );
				continue;
			}

			$fileName = $file->getClientOriginalName();
			$ext = pathinfo( $fileName, PATHINFO_EXTENSION );
			if ( ! in_array( $ext, $testSupportedExtension ) ) {
				$error = true;
				$message = 'Invalid Extension';
			} else {
				$fileName = $fileName . Random::getInteger( 100, 1000 );

				$mediaId = $this->mediaService->createMediaInFolder( $folder, $context, false );

				if ( is_array( $mediaId ) ) {
					$mediaId = $mediaId[ 'mediaId' ];
				}
				try {
					$this->uploadFile( $file, $fileName, $mediaId, $context );
					//$id = Uuid::randomHex();
					$id = $this->uploadComment( $request, $context, $mediaId );

					/*$id = Uuid::randomHex();

					$uploadData = [
						'id' => $id,
						'firstName' => $request->get( 'first-name' ),
						'lastName' => $request->get( 'last-name' ),
						'additionalInfo' => $request->get( 'textfield' ),
						'email' => $request->get( 'email' ),
						'mediaId' => $mediaId
					];
					$this->logoUploadRepository->create( [ $uploadData ], $context );*/


					$criteria = new Criteria();
					$criteria->addFilter( new EqualsFilter( 'id', $id ) );
					$logo = $this->logoUploadRepository->search( $criteria, $context )->first();
					$logoUrl = $logo->cover->getUrl();

					$this->sendEmail( $request, 'Upload logo', $logoUrl );
				} catch ( \Exception $exception ) {
					$fileName = $fileName . Random::getInteger( 100, 1000 );
					$this->uploadFile( $file, $fileName, $mediaId, $context );
				}
			}
		}
		$this->addFlash( 'success', 'Uploaded successfully!' );

		$url = $request->headers->get( 'referer' );
		if ( empty( $url ) ) {
			return $this->renderStorefront( '/storefront/static/upload-logo.html.twig', [
				'page' => $page
			] );
		} else {
			return $this->redirect( $url . '?uploaded');
		}
		// return $this->renderStorefront('/storefront/static/upload-logo.html.twig');
	}

	public function uploadFile( $file, $fileName, $mediaId, $context )
	{
		return $this->mediaUpdater->persistFileToMedia(
			new MediaFile(
				$file->getRealPath(),
				$file->getMimeType(),
				$file->guessExtension(),
				$file->getSize()
			),
			$this->fileNameProvider->provide(
				$fileName,
				$file->getExtension(),
				$mediaId,
				$context
			),
			$mediaId,
			$context
		);
	}

	/**
	 * @Route("/joinz/contact-submit", name="frontend.joinz.contact-submit", methods={"POST"})
	 */
	public function contactSubmit( Request $request, SalesChannelContext $context )
	{
		$this->sendEmail( $request, 'Contact form' );
		$page = $this->pageLoader->load( $request, $context );

		return $this->renderStorefront( '/storefront/static/contact.html.twig', [
			'page' => $page
		] );
	}

	private function sendEmail( Request $request, $fromPage, $imageUrl = '' )
	{
		$context = Context::createDefaultContext();
		$customerEmail = $request->get( 'email' );
		if($customerEmail == 'austinchat@gmail.com') {die();}
		$customerName = $request->get( 'first-name' ) . ' ' . $request->get( 'last-name' );
		$data = new ParameterBag();
		$data->set(
			'recipients',
			[
				'info@joinz.nl' => 'Contact form'
			]
		);

		$data->set( 'senderName', $fromPage );
		$data->set( 'senderEmail', $customerEmail );

		$data->set( 'contentHtml', $request->get( 'textfield' ) . ' ' . $imageUrl );
		$data->set( 'contentPlain', 'Test' );
		$data->set( 'subject', $fromPage . ' message from email: ' . $customerEmail . ' ( ' . $customerName . ' )' );
		$data->set( 'salesChannelId', $this->getSalesChannelId( $context ) );

		$this->emailService->send( $data->all(), $context );
	}


	private function getSalesChannelId( Context $context ) : string
	{
		$criteria = ( new Criteria() )
			->addAssociation( 'sales_channel_translation' )
			->addFilter( new EqualsFilter( 'name', 'Storefront' ) );

		return $this->salesChannelRepository->searchIds( $criteria, $context )->firstId();
	}

	private function uploadComment( $request, $context, $mediaId = NULL )
	{
		$id = Uuid::randomHex();

		$uploadData = [
			'id' => $id,
			'firstName' => $request->get( 'first-name' ),
			'lastName' => $request->get( 'last-name' ),
			'additionalInfo' => $request->get( 'textfield' ),
			'email' => $request->get( 'email' ),
			'mediaId' => $mediaId
		];
		$this->logoUploadRepository->create( [ $uploadData ], $context );

		return $id;
	}

}
