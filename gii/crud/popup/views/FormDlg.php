<?php

use yii\helpers\Html;
use yii\helpers\StringHelper;

/** @var yii\web\View $this */
/** @var dee\inertia\gii\crud\Generator $generator*/

$modelClass = StringHelper::basename($generator->modelClass);
$baseRoute = $generator->controllerID;
$class = $generator->modelClass;
$inputChunks = array_chunk($inputs, (count($inputs) + 1) / 2);
?>
<script setup>
import { router } from "@inertiajs/vue3";
import { ref } from 'vue';
import { $bus } from '@/composables/global';
import { useVForm, required, remote } from "@/composables/form";

const show = ref(false);
const form = useVForm('formRef', {
<?php foreach($forms as $key => $value):?>
    <?= $key ?>: <?= $value ? 'true' : 'false' ?>,
<?php endforeach; ?>
}, {
    create: '<?= $baseRoute ?>/create',
    update: '<?= $baseRoute ?>/update',
});

function open(row){
    form.$reset(row);
    show.value = true;
}

function save(event){
    event.then(({valid}) => {
        if(valid){
            form.$submit().then(() => {
                show.value = false;
                router.reload();
            }).catch(error => {
                $bus.emit('toast', error.response.statusText);
            });
        }
    });    
}

defineExpose({ open });
</script>
<template>
    <v-dialog v-model="show" persistent max-width="720">
        <v-form ref="formRef" @submit.prevent="save($event)">
            <v-card>
                <v-toolbar density="compact" :title="(form.$isNew ? 'New ' : 'Edit ') + '<?= $modelName ?>'">
                    <template v-slot:append>
                        <v-btn density="compact" size="small" icon="$close" @click="show = false"></v-btn> 
                    </template>
                </v-toolbar>
                <v-card-text>
                    <v-row>
<?php foreach($inputChunks as $parts): ?>
                        <v-col xl="6" md="6" sm="6" cols="12">
                            <v-row>
<?php foreach($parts as $input): ?>
                                <v-col class="py-1" cols="12">
<?php if($input['type'] != 'boolean'): ?>
                                    <v-text-field type="<?= $input['type'] ?>" name="<?= $input['field'] ?>" v-model="form.<?= $input['field'] ?>" label="<?= $input['label'] ?>"
                                        variant="outlined" density="compact" :rules="[<?= $input['required'] ? 'required' : 'remote' ?>]"></v-text-field>
<?php else: ?>
                                    <v-checkbox name="<?= $input['field'] ?>" v-model="form.<?= $input['field'] ?>" label="<?= $input['label'] ?>"
                                        variant="outlined" density="compact" :rules="[<?= $input['required'] ? 'required' : 'remote' ?>]"></v-checkbox>
<?php endif; ?>
                                </v-col>
<?php endforeach; ?>
                            </v-row>
                        </v-col>
<?php endforeach; ?>
                    </v-row>
                </v-card-text>
                <v-card-actions class="pt-0">
                    <v-spacer></v-spacer>
                    <v-btn color="green" text @click.native="show = false">Close</v-btn>
                    <v-btn dark color="error darken-1" text type="submit">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-form>
    </v-dialog>
</template>