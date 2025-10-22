import { reactive, useTemplateRef } from "vue";
import { useForm as inertiaForm, usePage, router } from "@inertiajs/vue3";
import axios from 'axios';
const { yiiUrl } = window;

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

export function useVForm(formRef, keys, routes) {
    const form = typeof formRef === 'string' ? useTemplateRef(formRef) : formRef;
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
        $isNew: computed(() => pks.length === 0 || pks.some(key => primaryKeys[key] === null)),
        $reset(row) {
            Object.entries(stateKeys).forEach(([key, val]) => {
                model[key] = row ? row[key] : null;
                if (val) {
                    primaryKeys[key] = row ? row[key] : null;
                }
            });
        },
        async $submit() {
            const post = {};
            Object.entries(stateKeys).forEach(([key, val]) => {
                post[key] = model[key];
            });
            const url = model.$isNew ? yiiUrl(routes.create) : yiiUrl(routes.update, primaryKeys);
            return axios.post(url, post).catch(error => {
                if (error.response.status == 422) {
                    const errors = error.response.data;
                    if (form.value) {
                        form.value.items.forEach(item => {
                            if (errors[item.id]) {
                                item.errorMessages.push(errors[item.id]);
                            }
                        });
                    }
                }
                throw error;
            });
        }
    });
    return model;
}

export function useForm(formRef, data) {
    const form = typeof formRef === 'string' ? useTemplateRef(formRef) : formRef;
    const keys = Object.keys(data);
    const model = reactive({
        ...data,
        $loading: false,
        $submit(options) {
            const post = {};
            keys.forEach(k => post[k] = model[k]);
            const opts = options || {};
            router.post(usePage().url, post, {
                onStart(visit) {
                    model.$loading = true;
                    if (opts.onStart) {
                        return opts.onStart(visit);
                    }
                },
                onCancel() {
                    model.$loading = false;
                    if (opts.onCancel) {
                        return opts.onCancel();
                    }
                },
                onFinish(visit) {
                    model.$loading = false;
                    if (opts.onFinish) {
                        return opts.onFinish(visit);
                    }
                },
                onSuccess(page) {
                    model.$loading = false;
                    if (opts.onSuccess) {
                        return opts.onSuccess(page);
                    }
                },
                onError(errors) {
                    model.$loading = false;
                    if (form.value) {
                        form.value.items.forEach(item => {
                            if (errors[item.id]) {
                                item.errorMessages.push(errors[item.id]);
                            }
                        });
                    }
                    if (opts.onError) {
                        return opts.onError(errors);
                    }
                },
            });
        }
    });
    return model;
}

export function useInertiaForm(formRef, data) {
    const form = typeof formRef === 'string' ? useTemplateRef(formRef) : formRef;
    const model = inertiaForm({
        ...data,
        $submit(options) {
            const opts = options || {};
            const onError = opts.onError;
            opts.onError = errors => {
                if (form.value) {
                    form.value.items.forEach(item => {
                        if (errors[item.id]) {
                            item.errorMessages.push(errors[item.id]);
                        }
                    });
                }
                if (onError) {
                    return onError(errors);
                }
            };
            model.post(usePage().url, opts);
        }
    });
    return model;
}