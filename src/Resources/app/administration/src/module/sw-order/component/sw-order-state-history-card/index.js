const { Component, Mixin } = Shopware;

Component.override('sw-order-state-history-card', {
    mixins: [
        Mixin.getByName('notification')
    ],

    methods: {
        onTransactionStateSelected(actionName) {
            let isSelected = true;

            const lineItems = this.order?.lineItems;

            lineItems.forEach((item) => {
                if (!item?.product?.extensions?.esd?.hasSerial) {
                    return;
                }

                if (item.product.extensions.esd?.serial?.length <= 0)
                {
                    return;
                }

                const availableSerials = item.product.extensions.esd.serial.filter(item => item === null);

                if (availableSerials.length <= 0) {
                    isSelected = false;
                }
            });

            if (!isSelected) {
                this.createNotificationError({
                    title: this.$tc('sas-esd.orderStatusChange.errorTitle'),
                    message: this.$tc('sas-esd.orderStatusChange.errorMessage')
                });

                return;
            }

            this.$super('onTransactionStateSelected', actionName)
        },
    },
});
