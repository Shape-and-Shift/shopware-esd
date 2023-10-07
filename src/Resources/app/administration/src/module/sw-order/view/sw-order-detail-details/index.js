import template from './sw-order-detail-details.html.twig';

const { Component, Mixin } = Shopware;

Component.override('sw-order-detail-details', {
    template,

    inject: [
        'esdSendMailService',
        'repositoryFactory',
        'acl',
    ],

    mixins: [
        Mixin.getByName('notification')
    ],

    data() {
        return {
            isSendMailLoading: false,
            isEnableDownloadButton: false,
            isEnableSerialButton: false
        }
    },

    methods: {
        createdComponent() {
            this.$super('createdComponent');
            this.getEnableMailButtons();
        },

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
