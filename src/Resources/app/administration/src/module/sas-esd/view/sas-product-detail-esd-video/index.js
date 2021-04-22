import template from './sas-product-detail-esd-video.html.twig';

const { Component, Mixin, Context } = Shopware;
const { Criteria, EntityCollection } = Shopware.Data;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

Component.register('sas-product-detail-esd-video', {
    template,

    inject: ['repositoryFactory', 'systemConfigApiService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            activeModal: '',
            fileAccept: 'video/*',
            selectedItems: null,
            isLoading: true,
            isLoadedEsd: false,
            isShowUploadProcessModal: false,
            uploadProcess: 0,
            fileNameUploading: '',
            isLoadingVideo: true,
            isPublicMedia: true,
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
            'esdVideos',
            'isLoadedEsdMedia'
        ]),

        esdRepository() {
            return this.repositoryFactory.create('sas_product_esd');
        },

        esdVideoRepository() {
            return this.repositoryFactory.create('sas_product_esd_video');
        },

        esdMediaRepository() {
            return this.repositoryFactory.create('sas_product_esd_media');
        },

        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        productRepository() {
            return this.repositoryFactory.create('product');
        },

        mediaColumns() {
            return this.getVideoColumns();
        }
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
        createEsdMediaCollection() {
            return new EntityCollection('/media', 'media', Shopware.Context.api);
        },

        createdComponent() {
            this.fetchMediaConfig();

            if (this.product.id !== this.parentProduct.id) {
                Shopware.State.commit('swProductEsdMedia/setIsLoadedEsdMedia', false);
                this.loadEsd();
                this.loadMedia();
                this.isLoading = false;
            }
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
                }
            }

            if (typeof this.product.extensions.esd !== 'undefined') {
                this.isLoadedEsd = true;
            }
        },

        loadMedia() {
            this.isLoading = true;
            const criteria = new Criteria();
            criteria.addAssociation('media');
            criteria.addFilter(Criteria.equals('esdId', this.product.extensions.esd.id));
            criteria.addFilter(Criteria.not('and', [Criteria.equals('mediaId', null)]));

            this.esdMediaRepository.search(criteria, Shopware.Context.api).then((esdMedia) => {
                this.product.extensions.esd.esdMedia = esdMedia;

                const esdMediaList = this.createEsdMediaCollection();
                Shopware.State.commit('swProductEsdMedia/setEsdMedia', esdMediaList);
                esdMedia.forEach((esdMedia) => {
                    if (esdMedia.media.mediaType.name === 'VIDEO') {
                        Shopware.State.commit('swProductEsdMedia/addEsdMedia', esdMedia);
                    }
                })

                this.loadEsdVideos();
                this.isLoading = false;
                Shopware.State.commit('swProductEsdMedia/setIsLoadedEsdMedia', true);
            });
        },

        createEsdVideoCollection() {
            return new EntityCollection(
                this.esdVideoRepository.route,
                this.esdVideoRepository.entityName,
                Shopware.Context.api
            );
        },

        loadEsdVideos() {
            if (this.product.extensions.esd.esdMedia.length) {
                const criteria = new Criteria();
                criteria.addFilter(Criteria.equalsAny('esdMediaId', this.product.extensions.esd.esdMedia.getIds()));
                this.esdVideoRepository.search(criteria, Shopware.Context.api).then((esdVideos) => {
                    const tempEsdVideos = this.createEsdVideoCollection();
                    Shopware.State.commit('swProductEsdMedia/setEsdVideos', tempEsdVideos);
                    esdVideos.forEach((esdVideo) => {
                        if (esdVideo.esdMedia.media.mediaType.name === 'VIDEO') {
                            Shopware.State.commit('swProductEsdMedia/addEsdVideo', esdVideo);
                        }
                    })
                });
            }
        },

        getEsdVideoOptionByMediaId(item) {
            const foundEsdVideos = this.esdVideos.filter((esdVideo) => {
                return esdVideo.esdMediaId === item.id;
            });

            if (foundEsdVideos.length >= 1 && (
                item.media.fileExtension.toLowerCase() === 'mp4' ||
                item.media.fileExtension.toLowerCase() === 'webp'
            )) {
                const esdVideo = foundEsdVideos[0];

                return esdVideo.option.toString();
            }

            return '2'; // return download only
        },

        onChangeEsdVideoOption(value, esdMedia) {
            const foundEsdVideos = this.esdVideos.filter((esdVideo) => {
                return esdVideo.esdMediaId === esdMedia.id;
            });

            const option = parseInt(value);
            if (
                esdMedia.media.fileExtension.toLowerCase() === 'mp4' ||
                esdMedia.media.fileExtension.toLowerCase() === 'webp') {
                if (foundEsdVideos.length >= 1) {
                    const esdVideo = foundEsdVideos[0];
                    esdVideo.option = option;
                    Shopware.State.commit('swProductEsdMedia/updateEsdVideo', esdVideo);
                } else {
                    this.createNewEsdVideo(esdMedia, esdMedia.media, option);
                }
            }
        },

        async createNewEsdVideo(esdMedia, mediaItem, option) {
            if (mediaItem.fileExtension.toLowerCase() === 'mp4' || mediaItem.fileExtension.toLowerCase() === 'webp') {
                const esdVideo = this.esdVideoRepository.create(Shopware.Context.api);
                esdVideo.esdMediaId = esdMedia.id;
                esdVideo.option = option;

                await this.esdVideoRepository.save(esdVideo, Shopware.Context.api);
            }
        },

        getVideoColumns() {
            return [{
                property: 'title',
                label: 'sas-esd.video.title',
                inlineEdit: 'string',
                allowResize: true
            }, {
                property: 'fileType',
                label: 'sas-esd.video.fileType'
            }, {
                property: 'option',
                label: 'sas-esd.video.option',
                inlineEdit: 'string'
            }];
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

            if (mediaItem.fileExtension.toLowerCase() === 'mp4' || mediaItem.fileExtension.toLowerCase() === 'webp') {
                await this.createNewEsdVideo(esdMedia, mediaItem, 0);
            }

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
            const esdMedia = this.createEsdMediaCollection();
            Shopware.State.commit('swProductEsdMedia/setEsdMedia', esdMedia);
            this.product.extensions.esd.esdMedia.forEach((esdMedia) => {
                if (esdMedia.media && esdMedia.mediaId) {
                    if (esdMedia.media.mediaType.name === 'VIDEO') {
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

        onDeleteEsdMediaItem(mediaId) {
            const foundIndex = this.product.extensions.esd.esdMedia
                .findIndex((esdMedia) => esdMedia.mediaId === mediaId);
            if (foundIndex < 0) {
                return;
            }

            this.product.extensions.esd.esdMedia[foundIndex].mediaId = null;
            this.getEsdMedia();
        },

        onDeleteEsdSelectedMedia() {
            Object.values(this.selectedItems).forEach((item) => {
                if (item.media && item.media.id) {
                    this.onDeleteEsdMediaItem(item.media.id);
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

        async onInlineEditSave(item) {
            const foundEsdVideos = this.esdVideos.filter((esdVideo) => {
                return esdVideo.esdMediaId === item.id;
            });

            this.isLoading = true;
            if (foundEsdVideos.length >= 1) {
                const esdVideo = foundEsdVideos[0];
                await this.esdVideoRepository.save(esdVideo, Shopware.Context.api);
                this.loadEsdVideos();
            }

            this.mediaRepository.save(item.media, Context.api).then(() => {
                this.getEsdMedia();
                this.createNotificationSuccess({
                    message: this.$tc('sas-esd.notification.messageSaveSuccess')
                });
            }).catch(() => {
                this.createNotificationError({
                    message: this.$tc('sas-esd.notification.messageSaveError')
                });
            });
            this.isLoading = false;
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

        onMediaUploadButtonOpenSidebar() {
            this.$root.$emit('sidebar-toggle-open');
        },
    }
})
