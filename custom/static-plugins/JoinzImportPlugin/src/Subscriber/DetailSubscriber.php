<?php declare(strict_types=1);

namespace JoinzImportPlugin\Subscriber;

use JoinzImportPlugin\Components\Helper\CustomerHelper;
use JoinzImportPlugin\Components\Helper\ProductHelper;
use Shopware\Core\Content\Product\Aggregate\ProductReview\ProductReviewEntity;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Page;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DetailSubscriber implements EventSubscriberInterface
{

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
     * @param ProductHelper $productHelper
     * @param CustomerHelper $customerHelper
     */
    public function __construct(ProductHelper $productHelper,
                                CustomerHelper $customerHelper)
    {
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
        $data['productMpn'] = $product->getManufacturerNumber();

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

        $page->addExtension('RichSnippetData', new ArrayEntity([
            'data' => $data
        ]));
    }
}
