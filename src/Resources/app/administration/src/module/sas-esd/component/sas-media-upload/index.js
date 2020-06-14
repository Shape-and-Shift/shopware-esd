Shopware.Component.extend('sas-media-upload', 'sw-media-upload-v2', {
    methods: {
        getMediaEntityForUpload() {
            const mediaItem = this.mediaRepository.create();
            mediaItem.mediaFolderId = this.mediaFolderId;
            mediaItem.private = true;

            return mediaItem;
        },
    },
});
