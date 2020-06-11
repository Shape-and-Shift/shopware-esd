import template from './sas-esd-modal-serial.html.twig';
import './sas-esd-modal-serial.scss';

const { Component, Context, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

Component.register('sas-esd-modal-serial', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isLoading: false,
            serials: ""
        };
    },

    computed: {
        ...mapState('swProductDetail', [
            'product'
        ]),

        serialRepository() {
            return this.repositoryFactory.create('sas_product_esd_serial');
        }

    },

    methods: {
        saveSerials() {
            this.isLoading = true;
            const lines = this.serials.split("\n");
            let promises = [];
            for (let line of lines) {
                let serial = this.serialRepository.create(Shopware.Context.api);
                serial.esdId = this.product.extensions.esd.id;
                serial.serial = line;
                promises.push(this.serialRepository.save(serial, Shopware.Context.api));
            }
            Promise.all(promises)
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
        }
    }
});
