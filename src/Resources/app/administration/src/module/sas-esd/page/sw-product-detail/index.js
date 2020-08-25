import template from './sw-product-detail.html.twig';
import swProductEsdMediaState from './state';

Shopware.Component.override('sw-product-detail', {
    template,

    beforeCreate() {
        Shopware.State.registerModule('swProductEsdMedia', swProductEsdMediaState);
    },

    beforeDestroy() {
        Shopware.State.unregisterModule('swProductEsdMedia');
    },

    watch: {
        productId() {
            this.clearEsd();
        }
    },

    methods: {
        clearEsd() {
            Shopware.State.commit('swProductEsdMedia/setIsLoadedEsdMedia', false);
            Shopware.State.commit('swProductEsdMedia/setEsdMedia', null);
        }
    }
});
