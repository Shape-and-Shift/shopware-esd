const { Component, Mixin } = Shopware;

Component.override('sw-order-detail', {
    computed: {
        orderCriteria() {
            const criteria = this.$super('orderCriteria');
            criteria.addAssociation('lineItems.product.esd.serial.esdOrder');

            return criteria;
        }
    },
});
