<?php declare( strict_types = 1 );

namespace JoinzImportPlugin\Controller;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Checkout\Cart\Exception\LineItemNotFoundException;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItemFactoryRegistry;
use Shopware\Core\Checkout\Cart\Order\OrderPersister;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderStates;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Shopware\Core\Content\Mail\Service\MailService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\Routing\StorefrontResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class JoinzCartController extends StorefrontController
{
	private $factory;

	private $cartService;
	private $orderService;
	private $orderPersister;
	private $orderRepository;
	private $emailService;
	protected $salesChannelRepository;
	protected $productRepository;

	public function __construct( LineItemFactoryRegistry $factory, CartService $cartService,
								 OrderService $orderService, OrderPersister $orderPersister,
								 EntityRepositoryInterface $orderRepository,
								 MailService $emailService,
								 EntityRepositoryInterface $salesChannelRepository,
								 EntityRepositoryInterface $productRepository
	)
	{
		$this->factory = $factory;
		$this->cartService = $cartService;
		$this->orderService = $orderService;
		$this->orderPersister = $orderPersister;
		$this->orderRepository = $orderRepository;
		$this->emailService = $emailService;
		$this->salesChannelRepository = $salesChannelRepository;
		$this->productRepository = $productRepository;
	}

	/**
	 * @Route("/joinz/addToCart", name="frontend.joinz.add-to-cart", methods={"POST"})
	 */
	public function addToCart( Request $request, SalesChannelContext $context, Cart $cart ): Response
	{
		if ( ! empty( $request->get( 'imprintId' ) ) ) {
			$imprintLineItem = $this->factory->create( [
				'type' => LineItem::PRODUCT_LINE_ITEM_TYPE, // Results in 'product'
				'referencedId' => $request->get( 'imprintId' ),
				'quantity' => (int)$request->get( 'quantity' ),
				'payload' => ['type' => 'imprint']
			], $context );
			$imprintLineItem->setDescription($request->get( 'imprintLocation' ));
            $this->cartService->add( $cart, $imprintLineItem, $context );



			$setupCost = (int)$request->get( 'setupCost' );
			if ( $setupCost > 0 ) {
				$setupCostSku = "JN_SETUP_COST_" . $setupCost;
				$setupCostId = $this->getProductIdFromSku( $setupCostSku );

				$setupCostLineItem = $this->factory->create( [
					'type' => LineItem::PRODUCT_LINE_ITEM_TYPE, // Results in 'product'
					'referencedId' => $setupCostId,
					'quantity' => 1,
					'payload' => [
						'type' => 'setup_cost',
						'cost_net' => $setupCost
					]
				], $context );
				$this->cartService->add( $cart, $setupCostLineItem, $context );

			}
		}
		// Create product line item
		$productLineItem = $this->factory->create( [
			'type' => LineItem::PRODUCT_LINE_ITEM_TYPE, // Results in 'product'
			'referencedId' => $request->get( 'productId' ),
			'quantity' => (int)$request->get( 'quantity' ),
			'payload' => [
				'type' => 'product',
				'imprintLocation' => $request->get( 'imprintLocation' )
			]
		], $context );
		if ( isset( $imprintLineItem ) ) {
			$productLineItem->setPayload( [
				'imprint' =>
					[
						'label' => $imprintLineItem->getLabel(),
						'quantity' => $imprintLineItem->getQuantity(),
						'price' => $imprintLineItem->getPrice(),
						'id' => $imprintLineItem->getId(),
						'MaxPrintArea' => $imprintLineItem->getPayload()[ 'customFields' ][ 'MaxPrintArea' ] ?? '',
						'setupCost' => $setupCost > 0 ? $setupCost : false,
						'setupCostId' => $setupCostLineItem ? $setupCostLineItem->getId() : false
					]
			] );
		}
		$newCart = $this->cartService->add( $cart, $productLineItem, $context );
		$request->getSession()->set( 'cart', $newCart->getPrice()->getPositionPrice() );

		$params = [];

		$params[ 'quote' ] = false;
		//Coming from <button submit name=request-quote>
		if ( ! is_null( $request->get( 'request-quote' ) ) ) {
			$params[ 'quote' ] = true;
		}
		$request->getSession()->set( 'quote', $params[ 'quote' ] );
		return $this->redirectToRoute( 'frontend.checkout.cart.page', $params );
	}


	private function getProductIdFromSku( $sku )
	{
		$dbContext = Context::createDefaultContext();
		$criteria = new Criteria();
		$criteria->addFilter( new EqualsFilter( 'productNumber', $sku ) );

		$productId = $this->productRepository->searchIds( $criteria, $dbContext )->firstId();
		if ( $productId ) {
			return $productId;
		}

		return NULL;
	}

	/**
	 * Returns an array of service types required by such instances, optionally keyed by the service names used internally.
	 *
	 * For mandatory dependencies:
	 *
	 *  * ['logger' => 'Psr\Log\LoggerInterface'] means the objects use the "logger" name
	 *    internally to fetch a service which must implement Psr\Log\LoggerInterface.
	 *  * ['loggers' => 'Psr\Log\LoggerInterface[]'] means the objects use the "loggers" name
	 *    internally to fetch an iterable of Psr\Log\LoggerInterface instances.
	 *  * ['Psr\Log\LoggerInterface'] is a shortcut for
	 *  * ['Psr\Log\LoggerInterface' => 'Psr\Log\LoggerInterface']
	 *
	 * otherwise:
	 *
	 *  * ['logger' => '?Psr\Log\LoggerInterface'] denotes an optional dependency
	 *  * ['loggers' => '?Psr\Log\LoggerInterface[]'] denotes an optional iterable dependency
	 *  * ['?Psr\Log\LoggerInterface'] is a shortcut for
	 *  * ['Psr\Log\LoggerInterface' => '?Psr\Log\LoggerInterface']
	 *
	 * @return array The required service types, optionally keyed by service names
	 */
	public static function getSubscribedServices()
	{

	}

	/**
	 * @Route("/joinz/deleteCartItem", name="frontend.joinz.delete-cart-item", methods={"POST"})
	 */
	public function deleteItem( Request $request, SalesChannelContext $context, Cart $cart ): Response
	{
		$productId = $request->get( 'productId' );
		$imprintId = $request->get( 'imprintId' );
		$setupCostId = $request->get( 'setupCostId' );
		try {
			if ( ! $cart->has( $productId ) ) {
				throw new LineItemNotFoundException( $productId );
			}

			$cart = $this->cartService->remove( $cart, $productId, $context );

			if ( ! empty( $imprintId ) ) {
				$cart = $this->cartService->remove( $cart, $imprintId, $context );
			}

			if ( ! empty( $setupCostId ) ) {
				$cart = $this->cartService->remove( $cart, $setupCostId, $context );
			}

			if ( ! $this->traceErrors( $cart ) ) {
				$this->addFlash( 'success', $this->trans( 'checkout.cartUpdateSuccess' ) );
			}
		} catch ( \Exception $exception ) {
			$this->addFlash( 'danger', $this->trans( 'error.message-default' ) );
		}
		$request->getSession()->set( 'cart', $cart->getPrice()->getPositionPrice() );
		return $this->createActionResponse( $request );
	}

	/**
	 * @Route("/joinz/changeCartItemQuantity/{id}", name="frontend.joinz.change-cart-item-quantity", defaults={"XmlHttpRequest": true}, methods={"POST"})
	 */
	public function changeQuantity( Request $request, SalesChannelContext $context, Cart $cart, string $id ): Response
	{
		$imprintId = $request->get( 'imprintId' );

		try {
			$quantity = $request->get( 'quantity' );

			if ( $quantity === null ) {
				throw new \InvalidArgumentException( 'quantity field is required' );
			}
			if ( ! $cart->has( $id ) ) {
				throw new LineItemNotFoundException( $id );
			}

			$cart = $this->cartService->changeQuantity( $cart, $id, (int)$quantity, $context );
			if ( isset( $imprintId ) && $imprintId != "") {
				$cart = $this->cartService->changeQuantity( $cart, $imprintId, (int)$quantity, $context );

				$imprint = $cart->get( $imprintId );
				$product = $cart->getLineItems()->get( $id )->setPayload( ['imprint' =>
					[
						'label' => $imprint->getLabel(),
						'quantity' => $imprint->getQuantity(),
						'price' => $imprint->getPrice(),
						'id' => $imprint->getId()]
				] );

				$cart->remove( $id );
				$cart->add( $product );
			}

			if ( ! $this->traceErrors( $cart ) ) {
				$this->addFlash( 'success', $this->trans( 'checkout.cartUpdateSuccess' ) );
			}
		} catch ( \Exception $exception ) {
			$this->addFlash( 'danger', $this->trans( 'error.message-default' ) );
		}
		$request->getSession()->set( 'cart', $cart->getPrice()->getPositionPrice() );
		return $this->createActionResponse( $request );
	}

	private function traceErrors( Cart $cart ): bool
	{
		if ( $cart->getErrors()->count() <= 0 ) {
			return false;
		}
		$this->addCartErrors( $cart );

		$cart->getErrors()->clear();

		return true;
	}

	/**
	 * @Route("/joinz/submitQuote", name="frontend.joinz.submit-quote", methods={"POST"})
	 */
	public function submitQuote( Request $request, SalesChannelContext $context, Cart $cart )
	{
		$this->sendEmail( $cart, 'Quote email', $context );
//		$or = $this->container->get(OrderService::class);

		$dbContext = Context::createDefaultContext();
		$orderId = $this->orderPersister->persist( $cart, $context );
		$this->orderRepository->update( [[
			'id' => $orderId,
			'customFields' => [
				'custom_isquote_bool' => true,
			]
		]], $dbContext );
//		dd(get_class_methods($this->orderService),get_class_methods($this->orderPersister));

		$ids = $cart->getLineItems()->getKeys();
		foreach ( $ids as $id ) {
			$this->cartService->remove( $cart, $id, $context );
		}

		$request->getSession()->remove( 'quote' );
		$request->getSession()->remove( 'cart' );

		return $this->renderStorefront( '/storefront/page/checkout/finish/index.html.twig', ['isQuote' => true] );
	}

	private function sendEmail( Cart $cart, $fromPage, SalesChannelContext $context )
	{
		$emailText = $this->fillEmailData( $cart, $context );
        $quoteEmailText = $this->fillEmailData( $cart, $context, true );
		$customerEmail = $context->getCustomer()->getEmail();
		$customerName = $context->getCustomer()->getActiveBillingAddress()->getFirstName() . ' ' . $context->getCustomer()->getActiveBillingAddress()->getLastName();

		$context = Context::createDefaultContext();
		$data = new ParameterBag();
		$data->set(
			'recipients',
			[
				'info@joinz.nl' => 'Quote form'
			]
		);

		$data->set( 'senderName', $fromPage );
		$data->set( 'senderEmail', 'info@joinz.nl' );

		$data->set( 'contentPlain', 'Test' );
		$data->set( 'subject', $fromPage . ' message from email: ' . $customerEmail . ' ( ' . $customerName . ' )' );
		$data->set( 'contentHtml', $emailText );
		$data->set( 'salesChannelId', $this->getSalesChannelId( $context ) );

		$this->emailService->send( $data->all(), $context );

		// SEND QUOTE EMAIL
        $dataCustomerEmail = new ParameterBag();
        $dataCustomerEmail->set(
            'recipients',
            [
                $customerEmail => $customerName
            ]
        );
        $dataCustomerEmail->set('senderName', 'Joinz');
        $dataCustomerEmail->set('senderEmail', 'info@joinz.nl');
        $dataCustomerEmail->set('subject', 'Je offerteaanvraag bij Joinz');
        $dataCustomerEmail->set('salesChannelId', $this->getSalesChannelId($context));
        $dataCustomerEmail->set( 'contentPlain', '-');
        $dataCustomerEmail->set('contentHtml', $quoteEmailText);
        $this->emailService->send($dataCustomerEmail->all(), $context);
	}

	private function fillEmailData( Cart $cart, SalesChannelContext $context, $is_customer_email = null )
	{
        $items = $cart->getLineItems();
        $customer = $context->getCustomer();
        $price = $cart->getPrice();
	    if ($is_customer_email) {
	        $emailText = '<table border="0" cellpadding="0" cellspacing="0" style="max-width:700px;margin:auto;color:#727272">
					<tbody>
						<tr>
							<td style="padding:0">
								<table width="100%" border="0" cellpadding="0" cellspacing="0">
									<tbody>
										<tr>
											<td style="padding:0">
												<h1 style="font-size:28px;line-height:26px;font-weight:500;color:#016F9F;">Bedankt voor je offerteaanvraag</h1>
												<p style="font-size:18px;line-height:26px;font-weight:400;">Hieronder de stappen naar een succesvolle samenwerking:</p>
											</td>
										</tr>
									</tbody>
								</table>
								<br>
								<table width="100%" border="0" cellpadding="0" cellspacing="0">
									<tbody>
										<tr>
											<td width="25%" style="padding:0">
												<table width="100%" border="0" cellpadding="0" cellspacing="0" height="180px">
													<tbody>
														<tr>
															<td style="padding:0;text-align:center;vertical-align:middle;">
																<img style="max-width:100%;" src="https://joinz.nl/media/e8/eb/cd/1660747364/1st-icon.png">
															</td>
														</tr>
													</tbody>
												</table>
											</td>
											<td width="25%" style="padding:0">
												<table width="100%" border="0" cellpadding="0" cellspacing="0" height="180px">
													<tbody>
														<tr>
															<td style="padding:0;text-align:center;vertical-align:middle;">
																<img style="max-width:100%;" src="https://joinz.nl/media/63/ef/43/1660747364/2nd-icon.png">
															</td>
														</tr>
													</tbody>
												</table>
											</td>
											<td width="25%" style="padding:0">
												<table width="100%" border="0" cellpadding="0" cellspacing="0" height="180px">
													<tbody>
														<tr>
															<td style="padding:0;text-align:center;vertical-align:middle;">
																<img style="max-width:100%;" src="https://joinz.nl/media/f9/39/93/1660747364/3rd-icon.png">
															</td>
														</tr>
													</tbody>
												</table>
											</td>
											<td width="25%" style="padding:0">
												<table width="100%" border="0" cellpadding="0" cellspacing="0" height="180px">
													<tbody>
														<tr>
															<td style="padding:0;text-align:center;vertical-align:middle;">
																<img style="max-width:100%;" src="https://joinz.nl/media/94/3d/77/1660747364/production.png">
															</td>
														</tr>
													</tbody>
												</table>
											</td>
										</tr>
										<tr>
											<td width="25%" style="padding:0;vertical-align:top;">
												<table width="100%" border="0" cellpadding="0" cellspacing="0">
													<tbody>
														<tr>
															<td style="padding:0 5px;vertical-align:top;">
																<h2 style="font-size:14px;line-height:18px;font-weight:400;color:#016F9F">1) Offerteaanvraag</h2>
																<p style="font-size:14px;line-height:18px;font-weight:400;color:#727272">Je offerteaanvraag is door ons ontvangen en wij gaan meteen aan de slag met een voorstel wat het beste bij jou past. E-mail je logo naar info@joinz.nl als je het nog niet via de website geüpload had.</p>
															</td>
														</tr>
													</tbody>
												</table>
											</td>
											<td width="25%" style="padding:0;vertical-align:top;">
												<table width="100%" border="0" cellpadding="0" cellspacing="0">
													<tbody>
														<tr>
															<td style="padding:0 5px;vertical-align:top;">
																<h2 style="font-size:14px;line-height:18px;font-weight:400;color:#016F9F">2) Ontvang offerte en ontwerp</h2>
																<p style="font-size:14px;line-height:18px;font-weight:400;color:#727272">Je ontvangt een offerte en digitaal ontwerp per e-mail.
Heb je een aanpassing? We horen het graag en gaan net zo lang door tot het goed is!</td>
														</tr>
													</tbody>
												</table>
											</td>
											<td width="25%" style="padding:0;vertical-align:top;">
												<table width="100%" border="0" cellpadding="0" cellspacing="0">
													<tbody>
														<tr>
															<td style="padding:0 5px;vertical-align:top;">
																<h2 style="font-size:14px;line-height:18px;font-weight:400;color:#016F9F">3) Alles akkoord?</h2>
																<p style="font-size:14px;line-height:18px;font-weight:400;color:#727272">Geef het ontwerp akkoord per e-mail zodat de productie gestart kan worden. De levertijd gaat vanaf deze dag in. Annuleren of wijzigen is hierna niet meer mogelijk.</p>
															</td>
														</tr>
													</tbody>
												</table>
											</td>
											<td width="25%" style="padding:0;vertical-align:top;">
												<table width="100%" border="0" cellpadding="0" cellspacing="0">
													<tbody>
														<tr>
															<td style="padding:0 5px;vertical-align:top;">
																<h2 style="font-size:14px;line-height:18px;font-weight:400;color:#016F9F">4) Productie & verzending</h2>
																<p style="font-size:14px;line-height:18px;font-weight:400;color:#727272">De producten worden voor je geproduceerd en je ontvangt track en trace als je bestelling klaar staat voor verzending.</p>
															</td>
														</tr>
													</tbody>
												</table>
											</td>
										</tr>
									</tbody>
								</table>
								<br><br><br>
								<table width="100%" border="0" cellpadding="0" cellspacing="0">
									<tbody>
										<tr>
											<td style="padding:0">
												<h1 style="font-size:28px;line-height:26px;font-weight:500;color:#016F9F;">Overzicht offerteaanvraag</h1>
											</td>
										</tr>
									</tbody>
								</table>
								<br>
								<table width="100%" border="0" cellpadding="0" cellspacing="0" style="color:#727272">
									<thead>
										<tr style="text-align:left">
											<th style="font-size:12px;font-weight:600;line-height:26px;">Artikelnummer </th>
											<th style="font-size:12px;font-weight:600;line-height:26px;">Afbeelding</th>
											<th style="font-size:12px;font-weight:600;line-height:26px;">Titel</th>
											<th style="font-size:12px;font-weight:600;line-height:26px;">Aantal</th>

										</tr>
									</thead>
									<tbody>';
	        foreach ($items as $item) {
                if ($item->getPayload()['type'] == 'product') {
	            $emailText .= '<tr>
											<td style="padding:0;font-size:12px;font-weight:400;line-height:12px;vertical-align:top;">
												'. $item->getPayload()['productNumber'] .'
											</td>
											<td style="padding:0">
												<img src="'. $item->getCover()->getUrl() .'" width="101px" height="101px">
											</td>
											<td style="padding:0;font-size:12px;font-weight:400;line-height:12px;vertical-align:top;">
												'. $item->getLabel() .'
											</td>
											<td style="padding:0;font-size:12px;font-weight:400;line-height:12px;vertical-align:top;">
												'. $item->getQuantity() .'
											</td>
											</tr>';
                }
            }
									$emailText .=	'</tbody>
								</table>
								<br><br><br>
								<table width="80%" border="0" cellpadding="0" cellspacing="0" style="color:#727272;">
									<tbody>
										<tr>
											<td style="padding:0;font-size:20px;font-weight:500;line-height:26px;">
												<h4>Factuuradres</h4>
											</td>
											<td style="padding:0;font-size:20px;font-weight:500;line-height:26px;">
												<h4>Afleveradres</h4>
											</td>
										</tr>
										<tr>
											<td style="padding:0;font-size:14px;font-weight:400;line-height:26px;">
												'. $customer->getCompany().'<br>'.$customer->getActiveBillingAddress()->getFirstName() .' '.
                                        $customer->getActiveBillingAddress()->getLastName().'<br>'. $customer->getActiveBillingAddress()->getStreet().'<br>
												'.$customer->getActiveBillingAddress()->getZipcode() .' '. $customer->getActiveBillingAddress()->getCity().'<br>
												'.$customer->getActiveBillingAddress()->getCountry()->getName().'
											</td>
											<td style="padding:0;font-size:14px;font-weight:400;line-height:26px;">
												'. $customer->getCompany().'<br>'.$customer->getActiveShippingAddress()->getFirstName() .' '.
                                        $customer->getActiveShippingAddress()->getLastName().'<br>'. $customer->getActiveShippingAddress()->getStreet().'<br>
												'.$customer->getActiveShippingAddress()->getZipcode() .' '. $customer->getActiveShippingAddress()->getCity().'<br>
												'.$customer->getActiveShippingAddress()->getCountry()->getName().'
											</td>
										</tr>
									</tbody>
								</table>
								<br><br><br>
								<table width="100%" border="0" cellpadding="0" cellspacing="0" style="color:#727272;">
									<tbody>
										<tr>
											<td style="border: 1px solid #016F9F;">
											</td>
										</tr>
									</tbody>
								</table>
								<br><br>
								<table width="100%" border="0" cellpadding="0" cellspacing="0" style="color:#727272;">
									<tbody>
										<tr>
											<td style="padding:0;">
												<h5 style="font-size:15px;font-weight:500;line-height:18px;">Voor alle transacties gelden leveringsvoorwaarden. Benieuwd? Zie: <a href="https://joinz.nl/leveringsvoorwaarden" target="_blank” style="color:#016F9F;" >joinz.nl/leveringsvoorwaarden</a></h5> <!--Link to terms and conditions -->
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
					</tbody>
				</table>';
        }
	    else {
            $quoteData = [];
            $quoteData[] = 'Customer name: ' . $customer->getActiveBillingAddress()->getFirstName() . ' ' . $context->getCustomer()->getActiveBillingAddress()->getLastName();
            $quoteData[] = 'Customer email: ' . $customer->getEmail();


            foreach ($items as $item) {
                if ($item->getPayload()['type'] == 'product') {
                    if (isset($item->getPayload()['imprint'])) {
                        $imprint = $item->getPayload()['imprint'];
                        $stuckPrice = $item->getPrice()->getUnitPrice() + $imprint['price']->getUnitPrice();
                    }
                    else {
                        $imprint = [
                            'label' => '',
                            'MaxPrintArea' => ''
                        ];
                        $stuckPrice = $item->getPrice()->getUnitPrice();
                    }
                    $quoteData [] = $item->getLabel() .
                        '  (' . $imprint['label'] .
                        ' ' . $imprint['MaxPrintArea'] .
                        '). ' . $item->getQuantity() .
                        ' stocks (' . $stuckPrice . ' per stuk)';
                }
            }
            $quoteData[] = 'TOTAL ITEMS: ' . $price->getNetPrice();
            $quoteData[] = 'BTW: ' . ($price->getTotalPrice() - $price->getNetPrice());
            $quoteData[] = 'TOTAAL INCL. BTW: ' . $price->getTotalPrice();

            $emailText = "<p>" . implode('<br>', $quoteData) . "</p>";
        }
		return $emailText;
	}

	private function getSalesChannelId( Context $context ): string
	{
		$criteria = ( new Criteria() )
			->addAssociation( 'sales_channel_translation' )
			->addFilter( new EqualsFilter( 'name', 'Storefront' ) );

		return $this->salesChannelRepository->searchIds( $criteria, $context )->firstId();
	}

}
