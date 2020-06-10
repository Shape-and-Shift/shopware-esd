import './module/sas-esd';

Shopware.Module.register('sas-esd-tab', {
    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.product.detail') {
            currentRoute.children.push({
                name: 'sas.product.detail.esd',
                path: '/sw/product/detail/:id/esd',
                component: 'sas-product-detail-esd',
                meta: {
                    parentPath: "sw.product.index"
                }
            });
        }
        next(currentRoute);
    }
});

