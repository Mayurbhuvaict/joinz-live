import template from './kplngi-product-order-refresh-button.html.twig';
import './kplngi-product-order-refresh-button.scss';

Shopware.Component.register('kplngi-product-order-refresh-button', {
    template,

    props: {
        isLoading: {
            type: Boolean,
            default: false
        },
        orderActive: {
            type: Boolean,
            default: false
        }
    },

    methods: {
        emitButtonClicked() {
            this.$emit('button-click');
        }
    }
})
