<script setup>
import { watch } from 'vue';

const props = defineProps({
    errors: Object,
});
const emit = defineEmits(['submit', 'preSubmit']);

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

function submit(event) {
    emit('preSubmit', event);
    event.then(({ valid }) => {
        if (valid) {
            emit('submit');
        }
    });
}
</script>
<template>
    <v-form ref="el" @submit.prevent="submit">
        <slot></slot>
    </v-form>
</template>