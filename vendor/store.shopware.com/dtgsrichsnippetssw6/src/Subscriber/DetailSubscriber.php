<?php declare(strict_types=1);

namespace Dtgs\RichSnippets\Subscriber;

use Dtgs\RichSnippets\Components\Helper\CustomerHelper;
use Dtgs\RichSnippets\Components\Helper\ProductHelper;
use Shopware\Core\Content\Product\Aggregate\ProductReview\ProductReviewEntity;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Page;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DetailSubscriber implements EventSubscriberInterface
{
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var ProductHelper
     */
    private $productHelper;

    /**
     * @var CustomerHelper
     */
    private $customerHelper;

    const BEST_RATING_POINTS = 5;
    const WORST_RATING_POINTS = 1;

    /**
     * DetailSubscriber constructor.
     *
     * @param SystemConfigService $systemConfigService
     * @param ProductHelper $productHelper
     * @param CustomerHelper $customerHelper
     */
    public function __construct(SystemConfigService $systemConfigService,
                                ProductHelper $productHelper,
                                CustomerHelper $customerHelper)
    {
        $this->systemConfigService = $systemConfigService;
        $this->productHelper = $productHelper;
        $this->customerHelper = $customerHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return[
            ProductPageLoadedEvent::class => 'onProductPageLoaded'
        ];
    }

    /**
     * Event für alle Seiten
     *
     * @param ProductPageLoadedEvent $event
     * @throws \Exception
     */
    public function onProductPageLoaded($event)
    {
        /** @var Page $page */
        $page = $event->getPage();
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannel()->getId();
        $productFromPage = $event->getPage()->getProduct();
        $product = $this->productHelper->getProductById($productFromPage->getId(), $event->getSalesChannelContext());
        $richSnippetConfig = $this->systemConfigService->get('DtgsRichSnippetsSw6.config', $salesChannelId);

        $data = array();
        //Stammdaten
        $data['productID'] = $product->getId();
        $data['productName'] = $product->getTranslation('name');
        $data['productImage'] = ($product->getCover() !== null) ? $product->getCover()->getMedia()->getUrl() : '';
        //@TODO: vollständige URL
        $data['productLink'] = ($product->getSeoUrls() !== null) ? $product->getSeoUrls()->first()->getSeoPathInfo() : '';
        $data['productPrice'] = 0;
        $data['productEAN'] = $product->getEan();
        $data['productSku'] = $product->getProductNumber();

        switch ($richSnippetConfig['fieldForMpn']) {
            case 'ean':
                $data['productMpn'] = $product->getEan();
                break;
            case 'suppliernumber':
            default:
                $data['productMpn'] = $product->getManufacturerNumber();
                break;
        }

        $data['brandName'] = ($product->getManufacturer() !== null) ? $product->getManufacturer()->getTranslation('name') : '';
        $data['description'] = '';
        //Beschreibung
        $description = $product->getTranslation('description');
        if($description && strlen($description) > 0) {
            $data['description'] = $description;
        }
        else {
            $data['description'] = '';
        }
        $data['description'] = html_entity_decode(strip_tags($data['description']));

        //Offer
        $data['priceCurrency'] = $event->getSalesChannelContext()->getCurrency()->getIsoCode();
        $price = ($productFromPage->getCalculatedPrices()->count()) ? $productFromPage->getCalculatedPrices()->first()->getUnitPrice() : $productFromPage->getCalculatedPrice()->getUnitPrice();
        $data['price'] = (is_float($price)) ? $price : str_replace(',', '.', $price);
        $data['itemCondition'] = 'http://schema.org/NewCondition';
        $data['availability'] = ($product->getStock() > 0) ? 'http://schema.org/InStock' : 'http://schema.org/OutOfStock';
        $data['priceValidUntil'] = date('Y-m-d', strtotime("+5 years"));
        $data['sellerName'] = $event->getSalesChannelContext()->getSalesChannel()->getName();

        //Reviews
        if(isset($richSnippetConfig['showReviewTexts']) && $product->getProductReviews()->count()) {
            $data['reviews'] = [];
            foreach ($product->getProductReviews() as $comment) {
                /** @var ProductReviewEntity $comment */
                if(!$comment->getStatus()) continue;
                //V6.0.1 - fix for reviews from guest users
                if($comment->getCustomerId()) {
                    $customer = $this->customerHelper->getCustomerById($comment->getCustomerId(), $event->getSalesChannelContext());
                    $name = $customer->getFirstName();
                } else {
                    $name = $comment->getExternalUser();
                }
                $review = [
                    "@type" => "Review",
                    "author" => [
                        "@type" => "Person",
                        "name" => $name,
                    ],
                    "datePublished" => $comment->getCreatedAt()->format('Y-m-d H:i:s'),
                    "description" => addslashes($comment->getContent()),
                    "name" => addslashes($comment->getTitle()),
                    "reviewRating" => [
                        "@type" => "Rating",
                        "bestRating" => self::BEST_RATING_POINTS,
                        "ratingValue" => $comment->getPoints(),
                        "worstRating" => self::WORST_RATING_POINTS
                    ]
                ];
                $data['reviews'][] = $review;
            }
            if(count($data['reviews']) > 0) {
                //Rating
                $data['ratingValue'] = $product->getRatingAverage();
                $data['bestRating'] = self::BEST_RATING_POINTS;
                $data['worstRating'] = self::WORST_RATING_POINTS;
                $data['reviewCount'] = $product->getProductReviews()->count();

                $data['reviewsEncoded'] = json_encode($data['reviews']);
            }
        }

        $page->addExtension('RichSnippetData', new ArrayEntity([
            'data' => $data
        ]));
    }
}
