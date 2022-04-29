import template from './sas-esd-modal-csv.html.twig';
import './sas-esd-modal-csv.scss';

import { drop, every, forEach, get, isArray, map, set } from 'lodash';
import Papa from 'papaparse';
import mimeTypes from "mime-types";

const { Component, Mixin } = Shopware;
const { mapState } = Shopware.Component.getComponentHelper();

Component.register('sas-esd-modal-csv', {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification')
    ],

    props: {
        value: Array,
        url: {
            type: String
        },
        esdId: {
            type: String
        },
        mapFields: {
            required: true
        },
        callback: {
            type: Function,
            default: () => ({})
        },
        catch: {
            type: Function,
            default: () => ({})
        },
        finally: {
            type: Function,
            default: () => ({})
        },
        parseConfig: {
            type: Object,
            default() {
                return {};
            }
        },
        headers: {
            default: null
        },
        loadBtnText: {
            type: String,
            default: "Next"
        },
        submitBtnText: {
            type: String,
            default: "Submit"
        },
        autoMatchFields: {
            type: Boolean,
            default: false
        },
        autoMatchIgnoreCase: {
            type: Boolean,
            default: false
        },
        tableClass: {
            type: String,
            default: "table"
        },
        checkboxClass: {
            type: String,
            default: "form-check-input"
        },
        buttonClass: {
            type: String,
            default: "btn btn-primary"
        },
        inputClass: {
            type: String,
            default: "form-control-file"
        },
        validation: {
            type: Boolean,
            default: true,
        },
        fileMimeTypes: {
            type: Array,
            default: () => {
                return ["text/csv", "text/x-csv", "application/vnd.ms-excel", "text/plain"];
            }
        },
        tableSelectClass: {
            type: String,
            default: 'form-control'
        },
        canIgnore: {
            type: Boolean,
            default: false,
        }
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
                    label: item
                };
            });
        } else {
            this.fieldsToMap = map(this.mapFields, (label, key) => {
                return {
                    key: key,
                    label: label
                };
            });
        }
    },
    methods: {
        submit() {
            this.isLoading = true;
            const _this = this;
            this.form.csv = this.buildMappedCsv();
            this.$emit('input', this.form.csv);
            console.log(this.form.csv);

            const lines = this.form.csv;

            let stockAdditional = 0;
            let promises = [];
            for (let line of lines) {
                console.log(line.esdId);

                let serial = this.serialRepository.create(Shopware.Context.api);
                serial.esdId = this.esdId;
                serial.serial = line.serial;
                promises.push(this.serialRepository.save(serial, Shopware.Context.api).then(() => {
                    stockAdditional++;
                }));
            }

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
                            'sas-esd.notification.success'
                        )
                    });
                })
                .catch((error) => {
                    this.isLoading = false;
                    this.createNotificationError({
                        title: this.$root.$tc('global.default.error'),
                        message: error
                    });
                })

            _this.callback(this.form.csv);
        },
        buildMappedCsv() {
            const _this = this;
            let csv = this.hasHeaders ? drop(this.csv) : this.csv;
            return map(csv, (row) => {
                let newRow = {};
                forEach(_this.map, (column, field) => {
                    set(newRow, field, get(row, column));
                });
                return newRow;
            });
        },
        validFileMimeType() {
            let file = this.$refs.csv.files[0];
            const mimeType = file.type === "" ? mimeTypes.lookup(file.name) : file.type;
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
            const _this = this;
            this.readFile((output) => {
                _this.sample = get(Papa.parse(output, { preview: 2, skipEmptyLines: true }), "data");
                _this.csv = get(Papa.parse(output, { skipEmptyLines: true }), "data");
            });
        },
        readFile(callback) {
            let file = this.$refs.csv.files[0];
            if (file) {
                let reader = new FileReader();
                reader.readAsText(file, "UTF-8");
                reader.onload = function (evt) {
                    callback(evt.target.result);
                };
                reader.onerror = function () {
                };
            }
        },
        toggleHasHeaders() {
            this.hasHeaders = !this.hasHeaders;
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
        }
    },
    watch: {
        map: {
            deep: true,
            handler: function (newVal) {
                if (!this.url) {
                    let hasAllKeys = Array.isArray(this.mapFields) ? every(this.mapFields, function (item) {
                        return newVal.hasOwnProperty(item);
                    }) : every(this.mapFields, function (item, key) {
                        return newVal.hasOwnProperty(key);
                    });
                    if (hasAllKeys) {
                        this.createNotificationSuccess({
                            title: this.$root.$tc('global.default.success'),
                            message: this.$root.$tc(
                                'sas-esd.sas-esd-modal-csv.notification.success.ready'
                            )
                        });
                        this.isDisabled = false;
                    }
                }
            }
        },
        sample(newVal, oldVal) {
            if(this.autoMatchFields){
                if(newVal !== null){
                    this.fieldsToMap.forEach(field => {
                        newVal[0].forEach((columnName, index) => {
                            if(this.autoMatchIgnoreCase === true){
                                if(field.label.toLowerCase().trim() === columnName.toLowerCase().trim()){
                                    this.map[field.key] = index;
                                }
                            } else{
                                if(field.label.trim() === columnName.trim()){
                                    this.map[field.key] = index;
                                }
                            }
                        });
                    });
                }
            }
        }
    },
    computed: {
        ...mapState('swProductDetail', [
            'product'
        ]),
        serialRepository() {
            return this.repositoryFactory.create('sas_product_esd_serial');
        },
        firstRow() {
            return get(this, "sample.0");
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
});
