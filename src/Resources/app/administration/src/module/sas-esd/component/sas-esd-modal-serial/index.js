import template from './sas-esd-modal-serial.html.twig';
import './sas-esd-modal-serial.scss';

const { Component } = Shopware;

Component.register('sas-esd-modal-serial', {
    template,

    data() {
        return {
            isLoading: false
        };
    },
});
