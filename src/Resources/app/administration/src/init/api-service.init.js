const Application = Shopware.Application;
import SasMediaService from '../../src/core/service/api/sas.media.api.service';
Application.addServiceProvider('sasMediaService', (container) => {
    const initContainer = Application.getContainer('init');
    return new SasMediaService(initContainer.httpClient, container.loginService);
});
