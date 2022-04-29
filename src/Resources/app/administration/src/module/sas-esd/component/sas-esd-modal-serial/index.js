import template from './sas-esd-modal-serial.html.twig';
import './sas-esd-modal-serial.scss';

const { Component, Mixin } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

Component.register('sas-esd-modal-serial', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            isIncreaseStock: false,
            serials: ""
        };
    },

    computed: {
        ...mapState('swProductDetail', [
            'product'
        ]),

        serialRepository() {
            return this.repositoryFactory.create('sas_product_esd_serial');
        },

        productRepository() {
            return this.repositoryFactory.create('product');
        },
    },

    methods: {
        saveSerials() {
            this.isLoading = true;
            const lines = this.serials.split("\n");
            let promises = [];
            let stockAdditional = 0;

            for (let line of lines) {
                if (!line) {
                    return;
                }

                let serial = this.serialRepository.create(Shopware.Context.api);
                serial.esdId = this.product.extensions.esd.id;
                serial.serial = line;
                promises.push(this.serialRepository.save(serial, Shopware.Context.api).then(() => {
                    stockAdditional++;
                }));
            }

            Promise.all(promises)
                .then(() => {
                    return this.updateProductStock(stockAdditional);
                })
                .then(() => {
                    this.$emit('serial-updated');
                    this.$emit('modal-close');
                    this.isLoading = false;
                    this.createNotificationSuccess({
                        title: this.$root.$tc('global.default.success'),
                        message: this.$root.$tc(
                            'sas-esd.notification.success'
                        )
                    });
                })
                .catch((error) => {
                    this.createNotificationError({
                        title: this.$root.$tc('global.default.error'),
                        message: error
                    });
                })
        },

        updateProductStock(stockAdditional) {
            if (!this.isIncreaseStock || stockAdditional <= 0) {
                return Promise.resolve();
            }

            this.product.stock += stockAdditional;
            return this.productRepository.save(this.product, Shopware.Context.api).then(() => {
                this.$emit('load-product');
            });
        }
    }
});
