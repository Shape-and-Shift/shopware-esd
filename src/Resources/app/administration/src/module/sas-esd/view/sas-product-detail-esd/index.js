import template from './sas-product-detail-esd.html.twig';
import './sas-product-detail-esd.scss';

const { Component } = Shopware;
const { Criteria, EntityCollection } = Shopware.Data;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

Component.register('sas-product-detail-esd', {
    template,

    inject: ['repositoryFactory'],

    data() {
        return {
            activeModal: '',
            fileAccept: 'application/pdf, image/*',
            selectedItems: null,
            isLoading: true,
            isLoadedEsd: false
        };
    },

    computed: {
        ...mapState('swProductDetail', [
            'product'
        ]),

        ...mapGetters('swProductDetail', {
            isStoreLoading: 'isLoading'
        }),

        ...mapState('swProductEsdMedia', [
            'esdMedia',
            'isLoadedEsdMedia'
        ]),

        esdRepository() {
            return this.repositoryFactory.create('sas_product_esd');
        },

        esdMediaRepository() {
            return this.repositoryFactory.create('sas_product_esd_media');
        },

        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        mediaColumns() {
            return this.getMediaColumns();
        }

    },

    watch: {
        isStoreLoading: {
            handler() {
                if (this.isStoreLoading === false) {
                    this.loadEsd();
                }
            }
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
            if (!this.isStoreLoading && !this.isLoadedEsd) {
                /**
                 * We need to check if the extension esd exist,
                 * otherwise we would get an issue because it's undefined
                 */
                if (typeof this.product.extensions.esd === 'undefined') {
                    /* if not, create the relationship, so extensions.esd is available */
                    const esdExtension = this.esdRepository.create(this.context);
                    esdExtension.productId = this.product.id;
                    this.product.extensions.esd = esdExtension;
                    this.isLoading = false;
                } else if (!this.isLoadedEsdMedia) {
                    const criteria = new Criteria();
                    criteria.addAssociation('media');
                    criteria.addFilter(Criteria.equals('esdId', this.product.extensions.esd.id));
                    criteria.addFilter(Criteria.not('and', [Criteria.equals('mediaId', null)]));

                    this.esdMediaRepository.search(criteria, Shopware.Context.api).then((esdMedia) => {
                        this.product.extensions.esd.esdMedia = esdMedia;

                        const esdMediaList = this.createMediaCollection();
                        Shopware.State.commit('swProductEsdMedia/setEsdMedia', esdMediaList);
                        esdMedia.forEach((esdMedia) => {
                            Shopware.State.commit('swProductEsdMedia/addEsdMedia', esdMedia.media);
                        })

                        this.isLoading = false;
                        Shopware.State.commit('swProductEsdMedia/setIsLoadedEsdMedia', true);
                    });
                }
            }

            if (typeof this.product.extensions.esd !== 'undefined') {
                this.isLoadedEsd = true;
            }
        },

        getMediaColumns() {
            return [{
                property: 'fileName',
                label: 'sas-esd.media.fileName'
            }];
        },

        createEsdMediaAssoc(mediaItem) {
            const esdMedia = this.esdMediaRepository.create(Shopware.Context.api);
            esdMedia.esdId = this.product.extensions.esd.id;
            esdMedia.mediaId = mediaItem.id;
            esdMedia.media = mediaItem;
            esdMedia.media.private = true;

            this.product.extensions.esd.esdMedia.push(esdMedia);
            this.esdMedia.push(mediaItem);
        },

        getEsdMedia() {
            const esdMedia = this.createMediaCollection();
            Shopware.State.commit('swProductEsdMedia/setEsdMedia', esdMedia);
            this.product.extensions.esd.esdMedia.forEach((esdMedia) => {
                if (esdMedia.media && esdMedia.mediaId) {
                    Shopware.State.commit('swProductEsdMedia/addEsdMedia', esdMedia.media);
                }
            });
        },

        onSetMediaItem({ targetId }) {
            if (this.product.extensions.esd.esdMedia.some((esd) => esd.mediaId === targetId)) {
                return;
            }

            this.mediaRepository.get(targetId, Shopware.Context.api).then((updatedMedia) => {
                this.createEsdMediaAssoc(updatedMedia);
            });
        },

        onDeleteMediaItem(mediaId) {
            const foundIndex = this.product.extensions.esd.esdMedia.findIndex((esdMedia) => esdMedia.mediaId === mediaId);
            if (foundIndex < 0) {
                return;
            }

            this.product.extensions.esd.esdMedia[foundIndex].mediaId = null;
            this.getEsdMedia();
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
