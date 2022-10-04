import './component/sas-media-upload';
import './component/sas-product-esd-form';
import './component/sas-esd-modal-serial';
import './component/sas-esd-serial-overview';
import './component/sas-esd-modal-csv';
import './component/sas-process-bar';
import './component/sas-switch-esd-button';
import './component/sas-duplicated-media';

import './view/sas-product-detail-esd';
import './view/sas-product-detail-esd-video';

import './page/sw-product-detail';
import './page/sas-product-detail-esd';

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

            currentRoute.children.push({
                name: 'sas.product.detail.esd.video',
                path: '/sw/product/detail/:id/esd-video',
                component: 'sas-product-detail-esd-video',
                meta: {
                    parentPath: "sw.product.index"
                }
            });
        }

        next(currentRoute);
    }
});
