import template from './sw-order-detail-base.html.twig';

const { Component, Mixin } = Shopware;

Component.override('sw-order-detail-base', {
    template,

    inject: ['esdSendMailService'],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            order: null,
            isSendMailLoading: false,
            isEnableDownloadButton: false,
            isEnableSerialButton: false
        }
    },

    created() {
        this.getEnableMailButtons();
    },

    computed: {
        transaction() {
            for (let i = 0; i < this.order.transactions.length; i += 1) {
                if (this.order.transactions[i].stateMachineState.technicalName !== 'cancelled') {
                    return this.order.transactions[i];
                }
            }
            return this.order.transactions.last();
        },

        orderCriteria() {
            const criteria = this.$super('orderCriteria');
            criteria.addAssociation('lineItems.product.esd.serial.esdOrder');

            return criteria;
        }
    },

    methods: {
        getEnableMailButtons() {
            this.esdSendMailService.getEnableMailButtons(this.$route.params.id).then((data) => {
                this.isEnableDownloadButton = data.download;
                this.isEnableSerialButton = data.serial;
            });
        },

        onSendDownloadMail() {
            this.isSendMailLoading = true;
            this.esdSendMailService.sendMailDownload(this.order.id).then(() => {
                this.createNotificationSuccess({
                    title: this.$tc('sas-esd.esdMail.alertSendMailDownloadSuccessTitle'),
                    message: this.$tc('sas-esd.esdMail.alertSendMailDownloadSuccessMessage')
                });
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('sas-esd.esdMail.alertSendMailDownloadErrorTitle'),
                    message: this.$tc('sas-esd.esdMail.alertSendMailDownloadErrorMessage')
                });
            }).finally(() => {
                this.isSendMailLoading = false;
            });
        },

        onSendSerialMail() {
            this.isSendMailLoading = true;
            this.esdSendMailService.sendMailSerial(this.order.id).then(() => {
                this.isSendMailLoading = false;

                this.createNotificationSuccess({
                    title: this.$tc('sas-esd.esdMail.alertSendMailSerialSuccessTitle'),
                    message: this.$tc('sas-esd.esdMail.alertSendMailSerialSuccessMessage')
                })
            }).catch(() => {
                this.createNotificationError({
                    title: this.$tc('sas-esd.esdMail.alertSendMailSerialErrorTitle'),
                    message: this.$tc('sas-esd.esdMail.alertSendMailSerialErrorMessage')
                });
            }).finally(() => {
                this.isSendMailLoading = false;
            });
        }
    }
});
