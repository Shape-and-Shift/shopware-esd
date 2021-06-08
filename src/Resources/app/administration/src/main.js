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
