import './extension/kplngi-category-view';
import './view/kplngi-product-order';

import './view/kplngi-product-order/kplngi-product-order-refresh-button/index';

import './view/kplngi-product-order/kplngi-product-order-entity-listing/index';

Shopware.Module.register('kplngi-product-order', {
    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.category.detail') {
            currentRoute.children.push({
                name: 'kplngi.product.order',
                path: '/sw/category/index/:id/order',
                component: 'kplngi-product-order',
                meta: {
                    parentPath: 'sw.category.index'
                }
            });
        }
        next(currentRoute)
    }
});
