import template from './sas-switch-esd-button.html.twig';

const { Component, Mixin } = Shopware;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

Component.register('sas-switch-esd-button', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification')
    ],

    props: {
        esdType: {
            type: String,
            required: true,
            default: 'normal'
        },
        label: {
            type: String,
            required: true,
            default: ''
        },
        confirmMessage: {
            type: String,
            required: true,
            default: ''
        }
    },

    data() {
        return {
            isShowConfirmModal: false,
            isLoading: false
        }
    },

    computed: {
        ...mapGetters('swProductDetail', {
            isStoreLoading: 'isLoading'
        }),

        ...mapState('swProductDetail', [
            'product',
            'parentProduct'
        ]),

        productRepository() {
            return this.repositoryFactory.create('product');
        },
    },

    methods: {
        onConfirmChange() {
            this.isShowConfirmModal =  true;
        },

        onCancelChange() {
            this.isShowConfirmModal =  false;
        },

        onChange() {
            this.isShowConfirmModal =  false;
            this.isLoading = true;

            let routerName = 'sas.product.detail.esd';
            if (this.esdType === 'video') {
                routerName = 'sas.product.detail.esd.video';
            }

            this.productRepository.save(this.product, Shopware.Context.api).then(() => {
                this.$router.push({ name: routerName, params: { id: this.$route.params.id } });
                this.createNotificationSuccess({
                    message: this.$tc('sas-esd.esdChange.messageChangeSuccess')
                });
            });
        }
    }
});
