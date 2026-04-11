<script setup>
import { watch } from 'vue';

const props = defineProps({
    errors: Object,    
});
const emit = defineEmits(['submit', 'validSubmit', 'errorSubmit']);

const el = useTemplateRef('el');
watch(() => props.errors, errors => {
    if (el.value) {
        el.value.items.forEach(item => {
            if (errors[item.id]) {
                item.errorMessages.push(errors[item.id]);
            }
        });
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
});
</script>
<template>
    <v-form ref="el" @submit.prevent="onSubmit">
        <slot></slot>
    </v-form>
</template>