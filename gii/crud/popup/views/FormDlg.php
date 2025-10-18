<?php

use yii\helpers\Html;
use yii\helpers\StringHelper;

/** @var yii\web\View $this */
/** @var dee\inertia\gii\crud\Generator $generator*/

$modelClass = StringHelper::basename($generator->modelClass);
$baseRoute = $generator->controllerID;
$class = $generator->modelClass;
$pks = $class::primaryKey();

$inputChunks = array_chunk($inputs, (count($inputs) + 1) / 2);
$required = false;
foreach($inputs as $input){
    if($input['required']){
        $required = true;
        break;
    }
}
?>
<script setup>
import { router, useForm } from "@inertiajs/vue3";
import { reactive, ref, watch, useTemplateRef } from 'vue';
import { $bus } from '@/composables/global';
<?php if($required): ?>
import { required } from "@/composables/validation";
<?php endif; ?>
const {yiiUrl} = window;

const show = ref(false);
const errors = ref({});
const formRef = useTemplateRef('formRef');
const state = reactive({
<?php foreach($pks as $pk): ?>
    <?= $pk ?>: null,
<?php endforeach; ?>
});
const form = reactive({
<?php foreach($forms as $key => $value):?>
    <?= $key?>: null,
<?php endforeach; ?>
});

function open(row){
    if(row) {
<?php foreach($pks as $pk): ?>
        state.<?= $pk ?>= row.<?= $pk?>;
<?php endforeach; ?>

<?php foreach($forms as $key => $value):?>
        form.<?= $key?>= row.<?= $key?>;
<?php endforeach; ?>
    } else {
<?php foreach($pks as $pk): ?>
        state.<?= $pk ?>= null;
<?php endforeach; ?>

<?php foreach($forms as $key => $value):?>
        form.<?= $key?>= null;
<?php endforeach; ?>
    }
    show.value = true;
}

const createUrl = yiiUrl.post('<?= $baseRoute ?>/create');
function save(){
<?php
    $ifs = $ps = [];
    foreach($pks as $pk){
        $ifs[] = "state.$pk";
        $ps[] = "$pk: state.$pk";
    }
    $if = implode(' && ', $ifs);
    if(count($ifs) > 1){
        $if = "($if)";
    }
    $paramUrl = implode(', ', $ps);
?>
    let url = <?= $if ?> ? yiiUrl.post('<?= $baseRoute ?>/update', {<?= $paramUrl?>}) : createUrl;
    axios.post(url, form).then(r => {
        show.value = false;
        router.reload();
    }).catch(error => {
        $bus.emit(error.response.statusText);
        if(error.response.status == 422){
            errors.value = error.response.data;
        }
    });
}

watch(errors, errs => {
    if(formRef.value){
        formRef.value.items.forEach(item => {
            if(errs[item.id]){
                item.errorMessages.push(errs[item.id]);
            }
        });
    }
});
defineExpose({ open });
</script>
<template>
    <v-dialog v-model="show" persistent>
        <v-form ref="formRef" @submit.prevent="save()">
            <v-card :title="(<?= $if ?> ? 'Edit ' : 'New ') + '<?= $modelName ?>'">
                <template  v-slot:append>
                    <v-btn density="compact" size="small" icon="$close" @click="show = false"></v-btn> 
                </template>
                <v-card-text>
                    <v-row>
<?php foreach($inputChunks as $parts): ?>
                        <v-col class="py-1" xl="6" md="6" sm="6" cols="12">
                            <v-row>
<?php foreach($parts as $input): ?>
                                <v-col class="py-1" cols="12">
<?php if($input['type'] != 'boolean'): ?>
                                    <v-text-field type="<?= $input['type'] ?>" name="<?= $input['field'] ?>" v-model="form.<?= $input['field'] ?>" label="<?= $input['label'] ?>"
                                        variant="outlined" density="compact" <?= $input['required'] ? ':rules=[required]' : '' ?>></v-text-field>
<?php else: ?>
                                    <v-checkbox name="<?= $input['field'] ?>" v-model="form.<?= $input['field'] ?>" label="<?= $input['label'] ?>"
                                        variant="outlined" density="compact" <?= $input['required'] ? ':rules=[required]' : '' ?>></v-checkbox>
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