import SasMediaService from '../../src/core/service/api/sas.media.api.service';

const Application = Shopware.Application;
Application.addServiceProvider('sasMediaService', (container) => {
    const initContainer = Application.getContainer('init');
    return new SasMediaService(initContainer.httpClient, container.loginService);
});
