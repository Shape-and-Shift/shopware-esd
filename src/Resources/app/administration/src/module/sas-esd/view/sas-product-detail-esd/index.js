import template from './sas-product-detail-esd.html.twig';
import './sas-product-detail-esd.scss';

const { Component, Mixin } = Shopware;
const { Criteria, EntityCollection } = Shopware.Data;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

Component.register('sas-product-detail-esd', {
    template,

    inject: ['repositoryFactory', 'systemConfigApiService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            activeModal: '',
            fileAccept: '*/*',
            selectedItems: null,
            isLoading: true,
            isLoadedEsd: false,
            isShowDownloadMailAlert: false,
            isShowSerialMailAlert: false,
            isShowUploadProcessModal: false,
            uploadProcess: 0,
            fileNameUploading: '',
            isPublicMedia: true,
            isEsdVideo: false,
            isDisableZipFile: false
        };
    },

    computed: {
        ...mapState('swProductDetail', [
            'product',
            'parentProduct'
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

        mailTemplateRepository() {
            return this.repositoryFactory.create('mail_template');
        },

        mediaColumns() {
            return this.getMediaColumns();
        },

        productRepository() {
            return this.repositoryFactory.create('product');
        },

        productCriteria() {
            const criteria = new Criteria();

            criteria.getAssociation('media')
                .addSorting(Criteria.sort('position', 'ASC'));

            criteria.getAssociation('properties')
                .addSorting(Criteria.sort('name', 'ASC'));

            criteria.getAssociation('prices')
                .addSorting(Criteria.sort('quantityStart', 'ASC', true));

            criteria.getAssociation('tags')
                .addSorting(Criteria.sort('name', 'ASC'));

            criteria.getAssociation('seoUrls')
                .addFilter(Criteria.equals('isCanonical', true));

            criteria.getAssociation('crossSellings')
                .addSorting(Criteria.sort('position', 'ASC'))
                .getAssociation('assignedProducts')
                .addSorting(Criteria.sort('position', 'ASC'))
                .addAssociation('product')
                .getAssociation('product')
                .addAssociation('options.group');

            criteria
                .addAssociation('cover')
                .addAssociation('categories')
                .addAssociation('visibilities.salesChannel')
                .addAssociation('options')
                .addAssociation('configuratorSettings.option')
                .addAssociation('unit')
                .addAssociation('productReviews')
                .addAssociation('seoUrls')
                .addAssociation('mainCategories')
                .addAssociation('options.group')
                .addAssociation('customFieldSets')
                .addAssociation('featureSet')
                .addAssociation('cmsPage')
                .addAssociation('featureSet');

            criteria.getAssociation('manufacturer')
                .addAssociation('media');

            return criteria;
        },
    },

    watch: {
        isStoreLoading: {
            handler() {
                if (this.isStoreLoading === false) {
                    this.loadEsd();
                    this.loadMedia();
                }
            }
        }
    },

    created() {
        this.createdComponent();
    },

    methods: {
        createdComponent() {
            this.fetchMediaConfig();
            this.fetchEsdConfig();

            if (this.product.id !== this.parentProduct.id) {
                Shopware.State.commit('swProductEsdMedia/setIsLoadedEsdMedia', false);
                this.loadEsd();
                this.loadMedia();
            }
        },

        createMediaCollection() {
            return new EntityCollection('/esd-media', 'esd_media', Shopware.Context.api);
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
                    esdExtension.hasSerial = false;
                    this.product.extensions.esd = esdExtension;

                    this.productRepository.save(this.product, Shopware.Context.api).then(() => {
                        this.loadProduct();
                        this.isLoading = false;
                    });
                }
            }

            if (typeof this.product.extensions.esd !== 'undefined') {
                this.isLoadedEsd = true;
            }
        },

        loadProduct() {
            this.productRepository.get(this.product.id, Shopware.Context.api, this.productCriteria)
                .then((res) => {
                    Shopware.State.commit('swProductDetail/setProduct', res);
                });
        },

        loadMedia() {
            this.isLoading = true;
            const criteria = new Criteria();
            criteria.addAssociation('media');
            criteria.addFilter(Criteria.equals('esdId', this.product.extensions.esd.id));
            criteria.addFilter(Criteria.not('and', [Criteria.equals('mediaId', null)]));

            this.esdMediaRepository.search(criteria, Shopware.Context.api).then((esdMedia) => {
                this.product.extensions.esd.esdMedia = esdMedia;

                const esdMediaList = this.createMediaCollection();
                Shopware.State.commit('swProductEsdMedia/setEsdMedia', esdMediaList);
                esdMedia.forEach((esdMedia) => {
                    if (esdMedia.media.mediaType.name !== 'VIDEO') {
                        Shopware.State.commit('swProductEsdMedia/addEsdMedia', esdMedia);
                    }
                })

                this.isLoading = false;
                Shopware.State.commit('swProductEsdMedia/setIsLoadedEsdMedia', true);
            });
        },

        getMediaColumns() {
            let columns = [
                {
                    property: 'media.fileName',
                    label: 'sas-esd.media.fileName'
                }
            ];

            if (this.isDisableZipFile) {
                columns.push({
                    property: 'downloadLimit',
                    label: 'sas-esd.media.downloadLimit',
                    inlineEdit: 'string'
                });
            }

            return columns;
        },

        async createEsdMediaAssoc(mediaItem) {
            this.isLoading = true;
            const esdMedia = this.esdMediaRepository.create(Shopware.Context.api);
            esdMedia.esdId = this.product.extensions.esd.id;
            esdMedia.mediaId = mediaItem.id;
            esdMedia.media = mediaItem;
            esdMedia.media.private = !this.isPublicMedia;

            await this.esdMediaRepository.save(esdMedia, Shopware.Context.api);

            this.product.extensions.esd.esdMedia.push(esdMedia);

            this.productRepository.save(this.product, Shopware.Context.api).then(() => {
                this.loadMedia();
                this.createNotificationSuccess({
                    message: this.$tc('sas-esd.notification.messageSaveSuccess')
                });
            }).catch(() => {
                this.createNotificationError({
                    message: this.$tc('sas-esd.notification.messageSaveError')
                });
            }).finally(() => {
                this.isLoading = false;
            });
        },

        fetchMediaConfig() {
            this.systemConfigApiService.getValues('SasEsd.config')
                .then(response => {
                    this.isPublicMedia = response['SasEsd.config.isPublicMedia'];
                });
        },

        getEsdMedia() {
            const esdMedia = this.createMediaCollection();
            Shopware.State.commit('swProductEsdMedia/setEsdMedia', esdMedia);
            this.product.extensions.esd.esdMedia.forEach((esdMedia) => {
                if (esdMedia.media && esdMedia.mediaId) {
                    if (esdMedia.media.mediaType.name !== 'VIDEO') {
                        Shopware.State.commit('swProductEsdMedia/addEsdMedia', esdMedia);
                    }
                }
            });
        },

        async onSetMediaItem({ targetId }) {
            if (this.product.extensions.esd.esdMedia.some((esd) => esd.mediaId === targetId)) {
                return;
            }

            this.isLoading = true;
            if (this.product.extensions.esd.isNew) {
                await this.productRepository.save(this.product, Shopware.Context.api);
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
            Object.values(this.selectedItems).forEach((item) => {
                if (item.media && item.media.id) {
                    this.onDeleteMediaItem(item.media.id);
                }
            });
        },

        onSelectionChanged(selection) {
            this.selectedItems = selection;
        },

        onMediaDropped(dropItem) {
            // to be consistent refetch entity with repository
            this.onSetMediaItem({ targetId: dropItem.id });
        },

        onShowProcess({ fileName, process }) {
            this.uploadProcess = process;
            if (process > 0 && process < 100) {
                this.isShowUploadProcessModal = true;
                this.fileNameUploading = fileName;
            } else {
                this.isShowUploadProcessModal = false;
                this.uploadProcess = 0;
                this.fileNameUploading = '';
            }
        },

        fetchEsdConfig() {
            this.systemConfigApiService.getValues('SasEsd.config')
                .then(response => {
                    this.isEsdVideo = response['SasEsd.config.isEsdVideo'];
                    this.isDisableZipFile = response['SasEsd.config.isDisableZipFile'];
                });
        },

        onMediaUploadButtonOpenSidebar() {
            this.$root.$emit('sidebar-toggle-open');
        },

        async onInlineEditSave(esdMedia) {
            await this.esdMediaRepository.save(esdMedia, Shopware.Context.api);
        },
    }
})
