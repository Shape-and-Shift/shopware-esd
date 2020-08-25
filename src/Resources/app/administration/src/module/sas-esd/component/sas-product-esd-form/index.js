import template from './sas-product-esd-form.html.twig';
import './sas-product-esd-form.scss';

const { Component } = Shopware;
const { mapGetters, mapState } = Component.getComponentHelper();

Component.register('sas-product-esd-form', {
    template,

    inject: ['repositoryFactory'],

    props: {
        esd: {
            type: Object,
            required: true
        }
    },

    computed: {
        ...mapState('swProductDetail', [
            'product'
        ]),

        ...mapGetters('swProductDetail', [
            'isLoading'
        ])
    }
})
