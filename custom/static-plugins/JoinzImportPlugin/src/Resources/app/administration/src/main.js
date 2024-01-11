import template from './page/sw-order-line-items-grid/sw-order-line-items-grid.html.twig';
import './page/sw-product-detail';
import './view/sw-product-detail-imprint';
import './sw-product-cross-selling-form-override/';
import './module/logo-upload-crud';
import './page/sw-order-line-items-grid/sw-order-line-items-grid.scss';

const { Component } = Shopware;

Component.override('sw-order-line-items-grid', {
    template
});

// Here you create your new route, refer to the mentioned guide for more information
Shopware.Module.register('sw-new-tab-custom', {
    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.product.detail') {
            currentRoute.children.push({
                name: 'sw.product.detail.imprint',
                path: '/sw/product/detail/:id/imprint',
                component: 'sw-product-detail-imprint',
                meta: {
                    parentPath: "sw.product.index"
                }
            });
        }
        next(currentRoute);
    }
});
