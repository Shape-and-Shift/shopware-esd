Shopware.Component.override('sw-media-upload', {
    methods: {
        getMediaEntityForUpload() {
            console.log('override private')
            const mediaItem = this.mediaItemStore.create();
            mediaItem.mediaFolderId = this.mediaFolderId;
            mediaItem.private = true;

            return mediaItem;
        },
    },
});
