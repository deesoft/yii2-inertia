<script setup>
import { watch } from 'vue';

const props = defineProps({
    errors: Object,
});
const emit = defineEmits(['submit', 'validSubmit', 'errorSubmit', 'remoteError']);

const remoteErrors = ref([]);
const el = useTemplateRef('el');
watch(() => props.errors, errors => {
    if (el.value) {
        el.value.items.forEach(item => {
            if (errors[item.id]) {
                item.errorMessages.push(errors[item.id]);
            }
        });
        const errs = Object.entries(errors).map(([field, error]) => ({field, error}))
            .filter(({field}) => !el.value.items.some(item => item.id == field));
        remoteErrors.value = errs;
        if(errs.length){
            emit('remoteError', errs);
        }
    }
}, { deep: true });

function onSubmit(event) {
    emit('submit', event);
    event.then(({ valid, errors }) => valid ? emit('validSubmit') : emit('errorSubmit', errors));
}
function submit()
{
    el.value.validate().then(({ valid, errors }) => valid ? emit('validSubmit') : emit('errorSubmit', errors));
}
defineExpose({
    submit,
    reset: () => el.value.reset(),
    resetValidation: () => el.value.resetValidation(),
    validate: () => el.value.validate(),
    remoteErrors,
});
</script>
<template>
    <v-form ref="el" @submit.prevent="onSubmit" v-slot="dataScope">
        <slot v-bind="{...dataScope, remoteErrors, clearRemoteError: () => remoteErrors = []}"></slot>
    </v-form>
</template>