import EventAwareAnalyticsEvent from 'src/plugin/google-analytics/event-aware-analytics-event';

export default class GtmAddToCartEvent extends EventAwareAnalyticsEvent
{
    supports() {
        return true;
    }

    getPluginName() {
        return 'AddToCart';
    }

    getEvents() {
        return {
            'beforeFormSubmit':  this._beforeFormSubmit.bind(this)
        };
    }

    _beforeFormSubmit(event) {
        if (!this.active) {
            return;
        }

        const formData = event.detail;
        let productId = null;

        formData.forEach((value, key) => {
            if (key.endsWith('[id]')) {
                productId = value;
            }
        });

        if (!productId) {
            console.warn('[codiverse GTM] Product ID could not be fetched. Skipping.');
            return;
        }

        let products = this.getProductsObjectFromFormData(formData, productId);

        dataLayer.push({
            'event': 'gtmAddToCart',
            'ecommerce': {
                'currencyCode': formData.get('dtgs-gtm-currency-code'),
                'add': {
                    'products': [products]
                }
            }
        });
    }

    getProductsObjectFromFormData(formData, productId) {

        //Product Array
        let products = {
            'name': formData.get('product-name'),
            'id': formData.get('dtgs-gtm-product-sku'),
            'quantity': formData.get('lineItems[' + productId + '][quantity]')
        };

        //Price und Brand Name optional
        if(formData.get('dtgs-gtm-product-price') !== null) Object.assign(products, {'price': formData.get('dtgs-gtm-product-price')});
        if(formData.get('brand-name') !== null) Object.assign(products, {'brand': formData.get('brand-name')});

        return products;

    }
}
