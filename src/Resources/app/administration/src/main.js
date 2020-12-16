import './module/sas-esd';
import './module/sw-order'
import './app/component/utils/sas-upload-listener'
import './init/api-service.init'
import EsdSendMailService from './api/esd-send-mail.api.service'

const { Application } = Shopware;

Application.addServiceProvider('esdSendMailService', (container) => {
    const initContainer = Application.getContainer('init');

    return new EsdSendMailService(initContainer.httpClient, container.loginService);
});

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

