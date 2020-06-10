import template from './sas-esd-serial-overview.html.twig';
import './sas-esd-serial-overview.scss';

const { Component } = Shopware;

Component.register('sas-esd-serial-overview', {
    template,

    data() {
        return {
            isLoading: false
        };
    },
});
