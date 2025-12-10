import template from './sas-switch-esd-button.html.twig';

const { Mixin } = Shopware;

export default {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification'),
    ],

    props: {
        esdType: {
            type: String,
            required: true,
            default: 'normal',
        },
        label: {
            type: String,
            required: true,
            default: '',
        },
        confirmMessage: {
            type: String,
            required: true,
            default: '',
        },
    },

    data() {
        return {
            isShowConfirmModal: false,
            isLoading: false,
        };
    },

    computed: {
        isStoreLoading() {
            return Shopware.Store.get('swProductDetail').isLoading;
        },

        product() {
            return Shopware.Store.get('swProductDetail').product;
        },

        parentProduct() {
            return Shopware.Store.get('swProductDetail').parentProduct;
        },

        productRepository() {
            return this.repositoryFactory.create('product');
        },
    },

    methods: {
        onConfirmChange() {
            this.isShowConfirmModal = true;
        },

        onCancelChange() {
            this.isShowConfirmModal = false;
        },

        async onChange() {
            this.isShowConfirmModal = false;
            this.isLoading = true;

            let routerName = 'sas.product.detail.esd';
            if (this.esdType === 'video') {
                routerName = 'sas.product.detail.esd.video';
            }

            await this.productRepository.save(this.product, Shopware.Context.api);
            await this.$router.push({ name: routerName, params: { id: this.$route.params.id } });
            // this.createNotificationSuccess({
            //     message: this.$tc('sas-esd.esdChange.messageChangeSuccess'),
            // });
        },
    },
};
