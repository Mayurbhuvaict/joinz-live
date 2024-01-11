import AnalyticsEvent from 'src/plugin/google-analytics/analytics-event';
import DomAccessHelper from 'src/helper/dom-access.helper';

export default class GtmRemoveFromCart extends AnalyticsEvent
{
    supports() {
        return true;
    }

    execute() {
        document.addEventListener('click', this._onRemoveFromCart.bind(this));
    }

    _onRemoveFromCart(event) {
        if (!this.active) {
            return;
        }

        const closest = event.target.closest('.cart-item-remove-button');
        if (!closest) {
            return;
        }

        const hiddenInput = DomAccessHelper.querySelector(event.target.closest('.cart-item-remove'), 'input[name=dtgs-gtm-product-sku]');
        if (!hiddenInput) {
            return;
        }

        dataLayer.push({
            'event': 'gtmRemoveFromCart',
            'ecommerce': {
                'remove': {
                    'products': [{
                        'id': hiddenInput.value
                    }]
                }
            }
        });
    }
}
