const { ApiService } = Shopware.Classes;

class EsdSendMailService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'esdsendmail') {
        super(httpClient, loginService, apiEndpoint);
    }

    sendMailDownload(orderId, additionalParams = {}) {
        const route = `/esd-mail/download`;
        const headers = {
            ...this.getBasicHeaders({})
        };

        return this.httpClient.post(route, {orderId}, { additionalParams, headers }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    sendMailSerial(orderId, additionalParams = {}) {
        const route = `/esd-mail/serial`;
        const headers = {
            ...this.getBasicHeaders({})
        };

        return this.httpClient.post(route, {orderId}, { additionalParams, headers }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getEnableMailButtons(orderId, additionalParams = {}) {
        const route = `/esd-mail/${orderId}/buttons`;
        const headers = {
            ...this.getBasicHeaders({})
        };

        return this.httpClient.get(route, { additionalParams, headers }).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export default EsdSendMailService;
