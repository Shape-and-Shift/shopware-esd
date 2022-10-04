const ApiService = Shopware.Classes.ApiService;
const MediaApiService = Shopware.ApiService.getByName('mediaService');
const { fileReader, array } = Shopware.Utils;
const UploadEventProcess = 'media-upload-process';

export const UploadEvents = {
    UPLOAD_ADDED: 'media-upload-add',
    UPLOAD_FINISHED: 'media-upload-finish',
    UPLOAD_FAILED: 'media-upload-fail',
    UPLOAD_CANCELED: 'media-upload-cancel',
};

/**
 * Gateway for the API end point "media"
 * @class
 * @extends ApiService
 */
class SasMediaApiService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'media') {
        super(httpClient, loginService, apiEndpoint);
        this.name = 'sasMediaService';
        this.mediaService = MediaApiService;
        this.tag = '';
        this.fileNameUploading = ''
    }

    runUploads(mediaService, tag) {
        this.mediaService = mediaService;
        const affectedUploads = array.remove(this.mediaService.uploads, (upload) => {
            return upload.uploadTag === tag;
        });
        const affectedListeners = this.mediaService.getListenerForTag(tag);

        if (affectedUploads.length === 0) {
            return Promise.resolve();
        }
        this.tag = tag;

        const totalUploads = affectedUploads.length;
        let successUploads = 0;
        let failureUploads = 0;
        return Promise.all(affectedUploads.map((task) => {
            if (task.running) {
                return Promise.resolve();
            }

            task.running = true;
            return this._startUpload(task).then(() => {
                task.running = false;
                successUploads += 1;
                affectedListeners.forEach((listener) => {
                    listener(this.mediaService._createUploadEvent(
                        UploadEvents.UPLOAD_FINISHED,
                        tag,
                        {
                            targetId: task.targetId,
                            successAmount: successUploads,
                            failureAmount: failureUploads,
                            totalAmount: totalUploads
                        }
                    ));
                });
            }).catch((cause) => {
                task.plugin = 'ESD';
                task.error = cause;
                task.running = false;
                failureUploads += 1;
                task.successAmount = successUploads;
                task.failureAmount = failureUploads;
                task.totalAmount = totalUploads;
                affectedListeners.forEach((listener) => {
                    listener(this.mediaService._createUploadEvent(
                        UploadEvents.UPLOAD_FAILED,
                        tag,
                        task
                    ));
                });
            });
        }));
    }

    _startUpload(task) {
        this.fileNameUploading = task.fileName;

        if (task.src instanceof File) {
            return fileReader.readAsArrayBuffer(task.src).then((buffer) => {
                return this.uploadMediaById(
                    task.targetId,
                    task.src.type,
                    buffer,
                    task.extension,
                    task.fileName
                );
            });
        }

        if (task.src instanceof URL) {
            return this.uploadMediaFromUrl(
                task.targetId,
                task.src.href,
                task.extension,
                task.fileName
            );
        }

        return Promise.reject(new Error('src of upload must either be an instance of File or URL'));
    }

    uploadMediaById(id, mimeType, data, extension, fileName = id) {
        const apiRoute = `/_action/${this.getApiBasePath(id)}/upload`;
        const headers = this.getBasicHeaders({ 'Content-Type': mimeType });
        const params = {
            extension,
            fileName
        };

        const config = this.getHttpConfig(params, headers);

        return this.httpClient.post(
            apiRoute,
            data,
            config
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    uploadMediaFromUrl(id, url, extension, fileName = id) {
        const apiRoute = `/_action/${this.getApiBasePath(id)}/upload`;
        const headers = this.getBasicHeaders({ 'Content-Type': 'application/json' });
        const params = {
            extension,
            fileName
        };

        const body = JSON.stringify({ url });

        const config = this.getHttpConfig(params, headers);

        return this.httpClient.post(
            apiRoute,
            body,
            config
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getHttpConfig(params, headers) {
        return  {
            params,
            headers,
            onUploadProgress: function (progressEvent) {
                const process = Math.round((progressEvent.loaded / progressEvent.total) * 100);
                this.saveUploadProcess(process);
            }.bind(this)
        };
    }

    saveUploadProcess(process) {
        this.mediaService.getListenerForTag(this.tag).forEach((listener) => {
            listener(this.mediaService._createUploadEvent(
                UploadEventProcess, this.tag, { fileName: this.fileNameUploading, process })
            );
        });
    }

    getAdminSystemMedia(fileName, extension) {
        const apiRoute = `/_action/${this.getApiBasePath()}/esd`;
        return this.httpClient.get(
            apiRoute,
            {
                params: { fileName, extension },
                headers: this.getBasicHeaders(),
            },
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    getAdminSystemMediaById(mediaId) {
        const apiRoute = `/_action/${this.getApiBasePath()}/esd/${mediaId}`;
        return this.httpClient.get(
            apiRoute,
            {
                headers: this.getBasicHeaders(),
            },
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }

    provideName(fileName, extension, mediaId = null) {
        const apiRoute = `/_action/${this.getApiBasePath()}/esd/provide-name`;
        return this.httpClient.get(
            apiRoute,
            {
                params: { fileName, extension, mediaId },
                headers: this.getBasicHeaders(),
            },
        ).then((response) => {
            return ApiService.handleResponse(response);
        });
    }
}

export { SasMediaApiService as default, UploadEventProcess };
