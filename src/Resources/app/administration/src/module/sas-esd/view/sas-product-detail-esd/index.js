import template from './sas-product-detail-esd.html.twig';
import './sas-product-detail-esd.scss';

const { Component, Context } = Shopware;
const { Criteria } = Shopware.Data;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

Component.register('sas-product-detail-esd', {
    template,

    inject: ['repositoryFactory'],

    data() {
        return {
            activeModal: '',
            fileAccept: 'application/pdf, image/*'
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

    },

    created() {
        console.log(this.product.getEntityName());
        /**
         * We need to check if the extension esd exist,
         * otherwise we would get an issue because it's undefined
         */
        if (typeof this.product.extensions.esd === 'undefined') {
            /* if not, create the relationship, so extensions.esd is available */
            const esdExtension = this.esdRepository.create(this.context);
            esdExtension.productId = this.product.id
            this.product.extensions.esd = esdExtension;
        }
    },

    methods: {
        onSetMediaItem({ targetId }) {
            this.mediaRepository.get(targetId, Shopware.Context.api).then((updatedMedia) => {
                this.product.extensions.esd.mediaId = targetId;
                this.product.extensions.esd.media = updatedMedia;
            });
        },

        onRemoveMediaItem() {
            this.product.extensions.esd.mediaId = null;
            this.product.extensions.esd.media = null;
        },

        onMediaDropped(dropItem) {
            // to be consistent refetch entity with repository
            this.onSetMediaItem({ targetId: dropItem.id });
        }
    }
})
