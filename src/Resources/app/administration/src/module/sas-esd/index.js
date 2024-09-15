
Shopware.Component.register('sas-upload-listener', () => import('../../app/component/utils/sas-upload-listener'));
Shopware.Component.extend('sas-media-upload', 'sw-media-upload-v2', () => import('./component/sas-media-upload'));
Shopware.Component.register('sas-product-esd-form', () => import('./component/sas-product-esd-form'));
Shopware.Component.register('sas-esd-modal-serial', () => import('./component/sas-esd-modal-serial'));
Shopware.Component.register('sas-esd-serial-overview', () => import('./component/sas-esd-serial-overview'));
Shopware.Component.register('sas-esd-modal-csv', () => import('./component/sas-esd-modal-csv'));
Shopware.Component.register('sas-process-bar', () => import('./component/sas-process-bar'));
Shopware.Component.register('sas-switch-esd-button', () => import('./component/sas-switch-esd-button'));
Shopware.Component.override('sw-duplicated-media-v2', () => import('./component/sas-duplicated-media'));
Shopware.Component.override('sw-product-detail', () => import('./page/sw-product-detail'));
Shopware.Component.register('sas-product-detail-esd', () => import('./page/sas-product-detail-esd'));
Shopware.Component.register('sas-product-detail-esd-normal', () => import('./view/sas-product-detail-esd'));
Shopware.Component.register('sas-product-detail-esd-video', () => import('./view/sas-product-detail-esd-video'));

Shopware.Module.register('sas-esd-tab', {
    routeMiddleware(next, currentRoute) {
        if (currentRoute.name === 'sw.product.detail') {
            currentRoute.children.push({
                name: 'sas.product.detail.esd',
                path: '/sw/product/detail/:id/esd',
                component: 'sas-product-detail-esd-normal',
                meta: {
                    parentPath: 'sw.product.index',
                },
            });

            currentRoute.children.push({
                name: 'sas.product.detail.esd.video',
                path: '/sw/product/detail/:id/esd-video',
                component: 'sas-product-detail-esd-video',
                meta: {
                    parentPath: 'sw.product.index',
                },
            });
        }

        next(currentRoute);
    },
});
