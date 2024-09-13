import template from './sas-process-bar.html.twig';
import './sas-process-bar.scss';

export default {
    template,

    props: {
        process: {
            type: Number,
            required: true,
        },
    },

    data() {
        return {
            styleObject: {
                width: '0%',
            },
        };
    },

    watch: {
        process: (newProcess) => {
            this.styleObject = {
                width: `${newProcess}%`,
            };
        },
    },
};
