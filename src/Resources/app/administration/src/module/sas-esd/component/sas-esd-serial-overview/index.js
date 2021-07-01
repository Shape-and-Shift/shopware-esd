import template from './sas-esd-serial-overview.html.twig';
import './sas-esd-serial-overview.scss';

const { Component, Mixin } = Shopware;
const { Criteria } = Shopware.Data;
const { mapState, mapGetters } = Shopware.Component.getComponentHelper();

Component.register('sas-esd-serial-overview', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('listing'),
    ],

    data() {
        return {
            serials: [],
            activeModal: "",
            showDeleteModal: false,
            showDeleteListModal: false,
            modalLoading: false,
            sortBy: 'serial',
            sortDirection: 'ASC',
            csv: [],
        };
    },

    computed: {
        ...mapState('swProductDetail', [
            'product',
            'variants',
        ]),

        ...mapGetters('swProductDetail', [
            'isLoading',
        ]),

        esdSerialRepository() {
            return this.repositoryFactory.create('sas_product_esd_serial');
        },

        serialColumns() {
            return [
                {
                    property: 'serial',
                    label: 'Serial',
                    allowResize: true,
                    sortable: true,
                },
                {
                    property: 'customer',
                    label: 'Assigned client',
                    allowResize: true,
                    sortable: true,
                }
            ];
        },
    },

    created() {
        this.getList();
    },

    methods: {
        getList()  {
            this.getSerials();
        },

        getSerials() {
            const criteria = new Criteria(this.page, this.limit);
            criteria.setTerm(this.term);
            criteria.addFilter(Criteria.equals('esdId', this.product.extensions.esd.id));
            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection));
            criteria.addAssociation('esdOrder.orderLineItem.order.orderCustomer');

            this.esdSerialRepository.search(criteria, Shopware.Context.api).then((serials) => {
                this.total = serials.total;
                this.serials = serials;
            });
        },

        updateSerials() {
            this.activeModal = '';
            this.getList();
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
                    message: this.$tc('sas-esd.serial.messageDeleteSuccess'),
                });

                this.getList();
            }).catch(() => {
                this.modalLoading = false;

                this.createNotificationError({
                    message: this.$tc('sas-esd.serial.messageDeleteError'),
                });
            });
        },

        onConfirmDeleteItems() {
            const promises = [];
            this.modalLoading = true;
            this.showDeleteListModal = false;

            Object.values(this.selectedItems).forEach((selectedItem) => {
                promises.push(this.esdSerialRepository.delete(selectedItem.id, Shopware.Context.api));
            });

            return Promise.all(promises).then(() => {
                this.modalLoading = false;

                this.createNotificationSuccess({
                    message: this.$tc('sas-esd.serial.messageDeleteSuccess'),
                });

                this.getList();
            }).catch(() => {
                this.modalLoading = false;

                this.createNotificationError({
                    message: this.$tc('sas-esd.serial.messageDeleteError'),
                });
            });
        },

        onSelectionChanged(selection) {
            this.selectedItems = selection;
        },

        openModal(value) {
            this.activeModal = value;
        },
    }
});
