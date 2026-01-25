<?php

use yii\helpers\Html;
use yii\helpers\StringHelper;

/** @var yii\web\View $this */
/** @var dee\inertia\gii\crud\Generator $generator*/

$modelClass = StringHelper::basename($generator->modelClass);
$baseRoute = $generator->controllerID;

$inputChunks = array_chunk($inputs, (count($inputs) + 1) / 2);
?>
<script setup>
import { required, remote } from "@/composables/form";
import { useForm, usePage } from "@inertiajs/vue3";
const { yiiUrl } = window;

const page = usePage();
const props = defineProps({
    model: Object,
});

const form = useForm({
<?php foreach($forms as $key => $value):?>
<?php if ($value != 'ai'): ?>
    <?= $key?>: props.model.<?= $key?>,
<?php endif; ?>
<?php endforeach; ?>
});

function save() {
    form.post(page.url);
}
</script>
<template>
    <v-container fluid>
        <v-row dense>
            <v-col cols="12">
                <p>
                    <Link :href="yiiUrl.home" class="text-decoration-none"><v-icon>mdi-home</v-icon></Link> /
                    <Link :href="yiiUrl('<?= $baseRoute ?>')" >List <?= $modelName ?></Link> /
                    <span >Create <?= $modelName ?></span>
                </p>
            </v-col>
            <v-col cols="12">
                <DForm :errors="form.errors" @submit="save()">
                    <v-card>
                        <v-toolbar density="compact">
                            <v-btn density="compact" icon="mdi-arrow-left" @click="yiiUrl.back()">
                            </v-btn>
                            <v-toolbar-title >Create <?= $modelName ?></v-toolbar-title>
                        </v-toolbar> 
                        <v-progress-linear indeterminate v-if="form.processing"></v-progress-linear>
                        <v-divider/>
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
                        <v-divider></v-divider>
                        <v-toolbar density="compact">
                            <v-spacer></v-spacer>
                            <v-btn :loading="form.processing" variant="flat" color="primary" type="submit">Save</v-btn>
                        </v-toolbar>
                    </v-card>
                </DForm>
            </v-col>
        </v-row>
    </v-container>
</template>