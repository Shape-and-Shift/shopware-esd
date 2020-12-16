import template from './sas-process-bar.html.twig';
import './sas-process-bar.scss';

const { Component } = Shopware;

Component.register('sas-process-bar', {
    template,

    props: {
        process: {
            type: Number,
            required: true
        }
    },

    data() {
        return {
            styleObject: {
                width: '0%'
            }
        }
    },

    watch: {
        process: function(newProcess) {
            this.styleObject = {
                width: newProcess + '%'
            }
        }
    }
});
