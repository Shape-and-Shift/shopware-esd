import template from './sas-media-upload-v2.html.twig';

Shopware.Component.extend('sas-media-upload', 'sw-media-upload-v2', {
    template,

    props: {
        fileAccept: {
            type: String,
            required: false,
            default: 'image/*'
        }

    },

    methods: {
        getMediaEntityForUpload() {
            const mediaItem = this.mediaRepository.create();
            mediaItem.mediaFolderId = this.mediaFolderId;
            mediaItem.private = true;

            return mediaItem;
        },
    },
});
