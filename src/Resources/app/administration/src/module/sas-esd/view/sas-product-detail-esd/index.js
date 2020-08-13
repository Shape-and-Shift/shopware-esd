import template from './sas-product-detail-esd.html.twig';
import './sas-product-detail-esd.scss';

const { Component } = Shopware;
const { Criteria, EntityCollection } = Shopware.Data;
const { mapState } = Shopware.Component.getComponentHelper();

Component.register('sas-product-detail-esd', {
    template,

    inject: ['repositoryFactory'],

    data() {
        return {
            activeModal: '',
            fileAccept: 'application/pdf, image/*',
            esdMedia: null,
            selectedItems: null,
            isLoading: false
        };
    },

    computed: {
        ...mapState('swProductDetail', [
            'product'
        ]),

        esdRepository() {
            return this.repositoryFactory.create('sas_product_esd');
        },

        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        mediaItem() {
            return this.product.extensions.esd !== null ? this.product.extensions.esd.media : null;
        },

        mediaColumns() {
            return this.getMediaColumns();
        }

    },

    created() {
        this.loadEsd();
    },

    methods: {
        createMediaCollection() {
            return new EntityCollection('/media', 'media', Shopware.Context.api);
        },

        loadEsd() {
            this.isLoading = true;
            this.esdMedia = this.createMediaCollection();

            const criteria = new Criteria();
            criteria.addFilter(Criteria.equals('productId', this.product.id));
            criteria.addFilter(Criteria.not('and', [Criteria.equals('mediaId', null)]));
            this.esdRepository.search(criteria, Shopware.Context.api).then((esdList) => {
                this.product.extensions.esd = esdList;
                esdList.forEach((esd) => {
                    this.esdMedia.push(esd.media);
                })
                this.isLoading = false;
            });
        },

        getMediaColumns() {
            return [{
                property: 'fileName',
                label: 'sas-esd.media.fileName'
            }];
        },

        createEsdMediaAssoc(mediaItem) {
            const esdMedia = this.esdRepository.create(Shopware.Context.api);
            esdMedia.productId = this.product.id
            esdMedia.mediaId = mediaItem.id;
            esdMedia.media = mediaItem;
            esdMedia.media.private = true;

            this.product.extensions.esd.push(esdMedia);
            this.esdMedia.push(mediaItem);
        },

        getEsdMedia() {
            this.esdMedia = this.createMediaCollection();
            this.product.extensions.esd.forEach((esdMedia) => {
                if (esdMedia.media) {
                    this.esdMedia.push(esdMedia.media);
                }
            });
        },

        onSetMediaItem({ targetId }) {
            if (this.product.extensions.esd.find((esd) => esd.mediaId === targetId)) {
                return;
            }

            this.mediaRepository.get(targetId, Shopware.Context.api).then((updatedMedia) => {
                this.createEsdMediaAssoc(updatedMedia);
            });
        },

        onDeleteMediaItem(mediaId) {
            const foundItem = this.product.extensions.esd.find((esd) => esd.mediaId === mediaId);
            if (foundItem) {
                foundItem.mediaId = null;
                foundItem.media = null;

                this.getEsdMedia();
            }
        },

        onDeleteSelectedMedia() {
            Object.keys(this.selectedItems).forEach((mediaId) => {
                this.onDeleteMediaItem(mediaId);
            });
        },

        onSelectionChanged(selection) {
            this.selectedItems = selection;
        },

        onMediaDropped(dropItem) {
            // to be consistent refetch entity with repository
            this.onSetMediaItem({ targetId: dropItem.id });
        }
    }
})
