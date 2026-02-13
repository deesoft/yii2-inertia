import { computed, reactive } from "vue";
import axios from 'axios';

export const required = (value) => (value !== null && value !== '') || 'Field is required';
export function minLength(min) {
    return (value) => value === null || value.length < min || `Min ${min} characters`;
}
export function maxLength(max) {
    return (value) => value === null || value.length > max || `Max ${max} characters`;
}

const EMAIL_PATTERN = /^[a-z0-9]+(\.[_a-z0-9]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,15})$/;
export const email = value => value === null || EMAIL_PATTERN.test(value) || 'Invalid email';

const PHONE_PATTERN = /^(\+|0)\d{6,16}$/;
export const phone = value => value === null || PHONE_PATTERN.test(value) || 'Invalid phone';

const URL_PATTERN = /^(?:(?:(?:https?|ftp):)?\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})).?)(?::\d{2,5})?(?:[/?#]\S*)?$/i;
export const url = value => value === null || URL_PATTERN.test(value) || 'Invalid url';

export const remote = () => true;

export function useVForm(keys) {
    const stateKeys = { ...keys }, _primaryKeys = {}, _model = {}, pks = [];
    Object.entries(stateKeys).forEach(([key, val]) => {
        _model[key] = null;
        if (val) {
            _primaryKeys[key] = null;
            pks.push(key);
        }
    });
    const primaryKeys = reactive(_primaryKeys);
    const model = reactive({
        ..._model,
        $isNew: computed(() => pks.length === 0 || pks.some(key => primaryKeys[key] === null || primaryKeys[key] === '')),
        $keys: computed(() => primaryKeys),
        errors: null,
        $reset(row) {
            Object.entries(stateKeys).forEach(([key, val]) => {
                model[key] = row ? row[key] : null;
                if (val) {
                    primaryKeys[key] = row ? row[key] : null;
                }
            });
        },
        async $submit(url) {
            const post = {};
            Object.entries(stateKeys).forEach(([key, val]) => {
                post[key] = model[key];
            });
            const data = hasFiles(post) ? objectToFormData(post) : post;
            return axios.post(url, data).then(r => {
                if (model.errors) {
                    model.errors = null;
                }
                return r;
            }).catch(error => {
                if (error.response.status == 422) {
                    model.errors = error.response.data;
                }
                throw error;
            });
        }
    });
    return model;
}

/**
 * 
 * @param {any} data 
 * @returns {Boolean}
 */
function hasFiles(data) {
    return (
        data instanceof File ||
        data instanceof Blob ||
        (data instanceof FileList && data.length > 0) ||
        (data instanceof FormData && Array.from(data.values()).some((value) => hasFiles(value))) ||
        (typeof data === 'object' && data !== null && Object.values(data).some((value) => hasFiles(value)))
    );
}

/**
 * 
 * @param {Object} source 
 * @param {FormData|null} form 
 * @param {String|null} parentKey 
 * @returns {FormData}
 */
function objectToFormData(source, form, parentKey) {
    source = source || {};
    form = form || new FormData();
    for (const key in source) {
        if (Object.prototype.hasOwnProperty.call(source, key)) {
            append(form, composeKey(parentKey, key), source[key]);
        }
    }

    return form;
}

function composeKey(parent, key) {
    return parent ? parent + '[' + key + ']' : key;
}

function append(form, key, value) {
    if (Array.isArray(value)) {
        return Array.from(value.keys()).forEach((index) => append(form, composeKey(key, index.toString()), value[index]));
    } else if (value instanceof Date) {
        return form.append(key, value.toISOString());
    } else if (value instanceof File) {
        return form.append(key, value, value.name);
    } else if (value instanceof Blob) {
        return form.append(key, value);
    } else if (typeof value === 'boolean') {
        return form.append(key, value ? '1' : '0');
    } else if (typeof value === 'string') {
        return form.append(key, value);
    } else if (typeof value === 'number') {
        return form.append(key, `${value}`);
    } else if (value === null || value === undefined) {
        return form.append(key, '');
    }

    objectToFormData(value, form, key);
}