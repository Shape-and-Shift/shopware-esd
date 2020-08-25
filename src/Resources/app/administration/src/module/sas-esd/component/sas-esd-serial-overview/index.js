import template from './sas-esd-serial-overview.html.twig';
import './sas-esd-serial-overview.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

Component.register('sas-esd-serial-overview', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            serials: [],
            activeModal: "",
            showDeleteModal: false,
            modalLoading: false,
            csv: []
        };
    },

    computed: {
        ...mapState('swProductDetail', [
            'product',
            'variants'
        ]),

        ...mapGetters('swProductDetail', [
            'isLoading'
        ]),

        esdSerialRepository() {
            return this.repositoryFactory.create('sas_product_esd_serial');
        },

        serialColumns() {
            return [
                {
                    property: 'serial',
                    label: 'Serial',
                    allowResize: true
                },
                {
                    property: 'customer',
                    label: 'Assigned client',
                    allowResize: true
                }
            ];
        },
    },

    created() {
        this.getSerials();
    },

    methods: {
        getSerials() {
            const criteria = new Criteria(1, 10);
            criteria.addFilter(Criteria.equals('esdId', this.product.extensions.esd.id));
            criteria.addAssociation('esdOrder.orderLineItem.order.orderCustomer');

            this.esdSerialRepository.search(criteria, Shopware.Context.api).then((serials) => {
                this.serials = serials;
            });
        },

        updateSerials() {
            this.activeModal = '';
            this.getSerials();
        },

        onEsdDelete(item) {
            this.showDeleteModal = item.id;
        },

        onCloseDeleteModal() {
            this.showDeleteModal = false;
        },

        onConfirmDelete(item) {
            this.modalLoading = true;
            this.showDeleteModal = false;

            this.esdSerialRepository.delete(item.id, Shopware.Context.api).then(() => {
                this.modalLoading = false;

                this.createNotificationSuccess({
                    title: this.$tc('sw-product.variations.generatedListTitleDeleteError'),
                    message: this.$tc('sw-product.variations.generatedListMessageDeleteSuccess')
                });

                this.getSerials();
            });
        },

        openModal(value) {
            this.activeModal = value;
        },
    }
});
