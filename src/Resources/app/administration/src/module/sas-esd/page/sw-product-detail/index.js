import template from './sw-product-detail.html.twig';
import swProductEsdMediaState from './state';

const { Component, Mixin } = Shopware;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

Component.override('sw-product-detail', {
    template,

    inject: ['systemConfigApiService', 'repositoryFactory'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isShowTheEsdVideo: false
        }
    },

    beforeCreate() {
        Shopware.State.registerModule('swProductEsdMedia', swProductEsdMediaState);
    },

    beforeDestroy() {
        Shopware.State.unregisterModule('swProductEsdMedia');
    },

    watch: {
        productId() {
            this.clearEsd();
        }
    },

    created() {
        this.getConfigShowTheEsdVideo();
    },

    computed: {
        ...mapState('swProductDetail', [
            'product'
        ]),

        ...mapGetters('swProductDetail', [
            'isLoading'
        ]),

        esdMediaRepository() {
            return this.repositoryFactory.create('sas_product_esd_media');
        },

        esdVideoRepository() {
            return this.repositoryFactory.create('sas_product_esd_video');
        },
    },

    methods: {
        clearEsd() {
            Shopware.State.commit('swProductEsdMedia/setIsLoadedEsdMedia', false);
            Shopware.State.commit('swProductEsdMedia/setEsdMedia', null);
        },

        async getConfigShowTheEsdVideo() {
            const config = await this.systemConfigApiService.getValues('SasEsd.config');

            this.isShowTheEsdVideo = !!config['SasEsd.config.isEsdVideo'];
        },

        onAddItemToESD(mediaItem) {
            this.onSetMediaItem(mediaItem);
        },

        async onSetMediaItem(mediaItem) {
            if (this.product.extensions.esd.esdMedia.some((esd) => esd.mediaId === mediaItem.id)) {
                return;
            }

            if (this.$route.name === 'sas.product.detail.esd.video') {
                if (mediaItem.mediaType.name !== "VIDEO" && this.getConfigShowTheEsdVideo) {
                    this.createNotificationError({
                        message: this.$tc('sas-esd.videoDoesntSupport')
                    });
                    return;
                }
            }

            Shopware.State.commit('swProductDetail/setLoading', ['product', true]);
            if (this.product.extensions.esd.isNew) {
                await this.productRepository.save(this.product, Shopware.Context.api);
            }

            this.createEsdMediaAssoc(mediaItem).then(() => {
                Shopware.State.commit('swProductDetail/setLoading', ['product', false]);
            });
        },

        async createEsdMediaAssoc(mediaItem) {
            const esdMedia = this.esdMediaRepository.create(Shopware.Context.api);
            esdMedia.esdId = this.product.extensions.esd.id;
            esdMedia.mediaId = mediaItem.id;

            await this.esdMediaRepository.save(esdMedia, Shopware.Context.api);

            this.product.extensions.esd.esdMedia.push(esdMedia);

            if (this.isShowTheEsdVideo) {
                if (this.isVideoFileSupportPlay) {
                    await this.createNewEsdVideo(esdMedia, mediaItem, 0);
                }
            }

            this.productRepository.save(this.product, Shopware.Context.api).then(() => {
                this.createNotificationSuccess({
                    message: this.$tc('sas-esd.notification.messageSaveSuccess')
                });
            }).catch(() => {
                this.createNotificationError({
                    message: this.$tc('sas-esd.notification.messageSaveError')
                });
            });
        },

        async createNewEsdVideo(esdMedia, mediaItem, option) {
            if (this.isVideoFileSupportPlay(mediaItem)) {
                const esdVideo = this.esdVideoRepository.create(Shopware.Context.api);
                esdVideo.esdMediaId = esdMedia.id;
                esdVideo.option = option;

                await this.esdVideoRepository.save(esdVideo, Shopware.Context.api);
            }
        },

        isVideoFileSupportPlay(mediaItem) {
            return mediaItem.fileExtension.toLowerCase() === 'mp4' || mediaItem.fileExtension.toLowerCase() === 'webp'
        }
    }
});
