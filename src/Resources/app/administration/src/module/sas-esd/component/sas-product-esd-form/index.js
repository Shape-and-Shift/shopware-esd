import template from './sas-product-esd-form.html.twig';
import './sas-product-esd-form.scss';


export default {
    template,

    inject: ['repositoryFactory'],

    props: {
        esd: {
            type: Object,
            required: true,
        },
    },

    computed: {
        product() {
            return Shopware.Store.get('swProductDetail').product;
        },

        isLoading() {
            return Shopware.Store.get('swProductDetail').isLoading;
        },
    },
};
