import { drop, every, forEach, get, isArray, map, set } from 'lodash';
import Papa from 'papaparse';

const lookupMimeType = (fileName) => {
    const ext = fileName.split('.').pop().toLowerCase();
    const map = {
        csv: 'text/csv',
        txt: 'text/plain',
        xls: 'application/vnd.ms-excel',
    };
    return map[ext] || false;
};

import template from './sas-esd-modal-csv.html.twig';

import './sas-esd-modal-csv.scss';

const { Mixin } = Shopware;

export default {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification'),
    ],

    props: {
        value: Array,
        url: {
            type: String,
        },
        esdId: {
            type: String,
        },
        mapFields: {
            required: true,
        },
        callback: {
            type: Function,
            default: () => ({}),
        },
        catch: {
            type: Function,
            default: () => ({}),
        },
        finally: {
            type: Function,
            default: () => ({}),
        },
        parseConfig: {
            type: Object,
            default() {
                return {};
            },
        },
        headers: {
            default: null,
        },
        loadBtnText: {
            type: String,
            default: 'Next',
        },
        submitBtnText: {
            type: String,
            default: 'Submit',
        },
        autoMatchFields: {
            type: Boolean,
            default: false,
        },
        autoMatchIgnoreCase: {
            type: Boolean,
            default: false,
        },
        tableClass: {
            type: String,
            default: 'table',
        },
        checkboxClass: {
            type: String,
            default: 'form-check-input',
        },
        buttonClass: {
            type: String,
            default: 'btn btn-primary',
        },
        inputClass: {
            type: String,
            default: 'form-control-file',
        },
        validation: {
            type: Boolean,
            default: true,
        },
        fileMimeTypes: {
            type: Array,
            default: () => {
                return ['text/csv', 'text/x-csv', 'application/vnd.ms-excel', 'text/plain'];
            },
        },
        tableSelectClass: {
            type: String,
            default: 'form-control',
        },
        canIgnore: {
            type: Boolean,
            default: false,
        },
    },
    data: () => ({
        form: {
            csv: null,
        },
        fieldsToMap: [],
        map: {},
        hasHeaders: true,
        csv: null,
        sample: null,
        isValidFileMimeType: false,
        fileSelected: false,
        isLoading: false,
        isDisabled: true,
        isIncreaseStock: false,
    }),
    created() {
        this.hasHeaders = this.headers;
        if (isArray(this.mapFields)) {
            this.fieldsToMap = map(this.mapFields, (item) => {
                return {
                    key: item,
                    label: item,
                };
            });
        } else {
            this.fieldsToMap = map(this.mapFields, (label, key) => {
                return {
                    key: key,
                    label: label,
                };
            });
        }
    },
    methods: {
        submit() {
            this.isLoading = true;
            // @ts-ignore
            const _this = this;
            this.form.csv = this.buildMappedCsv();
            this.$emit('input', this.form.csv);

            const lines = this.form.csv;

            let stockAdditional = 0;
            const promises = [];
            lines.forEach((line) => {
                const serial = this.serialRepository.create(Shopware.Context.api);
                serial.esdId = this.esdId;
                serial.serial = line.serial;
                promises.push(this.serialRepository.save(serial, Shopware.Context.api).then(() => {
                    stockAdditional += 1;
                }));
            });

            Promise.all(promises)
                .then(() => {
                    return this.updateProductStock(stockAdditional);
                })
                .then(() => {
                    this.$emit('serial-updated');
                    this.isLoading = false;
                    this.createNotificationSuccess({
                        title: this.$root.$tc('global.default.success'),
                        message: this.$root.$tc(
                            'sas-esd.notification.success',
                        ),
                    });
                })
                .catch((error) => {
                    this.isLoading = false;
                    this.createNotificationError({
                        title: this.$root.$tc('global.default.error'),
                        message: error,
                    });
                });

            _this.callback(this.form.csv);
        },
        buildMappedCsv() {
            const csv = this.hasHeaders ? drop(this.csv) : this.csv;

            // @ts-ignore
            const _this = this;
            return map(csv, (row) => {
                const newRow = {};
                forEach(_this.map, (column, field) => {
                    set(newRow, field, get(row, column));
                });
                return newRow;
            });
        },
        validFileMimeType() {
            const file = this.$refs.csv.files[0];
            const mimeType = file.type === '' ? lookupMimeType(file.name) : file.type;
            if (file) {
                this.fileSelected = true;
                this.isValidFileMimeType = this.validation ? this.validateMimeType(mimeType) : true;
            } else {
                this.isValidFileMimeType = !this.validation;
                this.fileSelected = false;
            }
        },
        validateMimeType(type) {
            return this.fileMimeTypes.indexOf(type) > -1;
        },
        load() {
            // @ts-ignore
            const _this = this;
            this.readFile((output) => {
                _this.sample = get(Papa.parse(output, { preview: 2, skipEmptyLines: true }), 'data');
                _this.csv = get(Papa.parse(output, { skipEmptyLines: true }), 'data');
            });
        },
        readFile(callback) {
            const file = this.$refs.csv.files[0];
            if (file) {
                const reader = new FileReader();
                reader.readAsText(file, 'UTF-8');
                reader.onload = (evt) => {
                    callback(evt.target.result);
                };
                reader.onerror = () => {
                };
            }
        },
        toggleHasHeaders() {
            this.hasHeaders = !this.hasHeaders;
            this.map = this.hasHeaders ? { serial: null } : { serial: 0 };
        },
        makeId(id) {
            return `${id}${this._uid}`;
        },
        updateProductStock(stockAdditional) {
            if (!this.isIncreaseStock || stockAdditional <= 0) {
                return Promise.resolve();
            }

            this.product.stock += stockAdditional;
            return this.productRepository.save(this.product, Shopware.Context.api).then(() => {
                this.$emit('load-product');
            });
        },
    },
    watch: {
        map: {
            deep: true,
            immediate: true,
            handler(newVal) {
                if (!this.url) {
                    const hasAllKeys = Array.isArray(this.mapFields) ? every(this.mapFields, (item) => {
                        // @ts-ignore
                        return newVal.hasOwnProperty(item) && newVal[item] !== null;
                    }) : every(this.mapFields, (item, key) => {
                        // @ts-ignore
                        return newVal.hasOwnProperty(key) && newVal[key] !== null;
                    });
                    if (hasAllKeys) {
                        this.createNotificationSuccess({
                            title: this.$root.$tc('global.default.success'),
                            message: this.$root.$tc(
                                'sas-esd.sas-esd-modal-csv.notification.success.ready',
                            ),
                        });
                        this.isDisabled = false;
                    } else {
                        this.isDisabled = true;
                    }
                }
            },
        },
        sample(newVal) {
            if (this.autoMatchFields) {
                if (newVal !== null) {
                    this.fieldsToMap.forEach(field => {
                        newVal[0].forEach((columnName, index) => {
                            if (this.autoMatchIgnoreCase === true) {
                                if (field.label.toLowerCase().trim() === columnName.toLowerCase().trim()) {
                                    this.map[field.key] = index;
                                }
                            } else if (field.label.trim() === columnName.trim()) {
                                this.map[field.key] = index;
                            }
                        });
                    });
                }
            }
        },
        isValidFileMimeType() {
            if (this.isValidFileMimeType) {
                this.load();
                this.map = this.hasHeaders ? this.map : { serial: 0 };
            }
        },
    },
    computed: {
        product() {
            return Shopware.Store.get('swProductDetail').product;
        },
        serialRepository() {
            return this.repositoryFactory.create('sas_product_esd_serial');
        },
        firstRow() {
            return get(this, 'sample.0');
        },
        showErrorMessage() {
            return this.fileSelected && !this.isValidFileMimeType;
        },
        disabledNextButton() {
            return !this.isValidFileMimeType;
        },
        productRepository() {
            return this.repositoryFactory.create('product');
        },
    },
};
