<?php

use yii\helpers\Html;
use yii\helpers\StringHelper;

/** @var yii\web\View $this */
/** @var dee\inertia\gii\crud\Generator $generator*/

$modelClass = StringHelper::basename($generator->modelClass);
$baseRoute = $generator->controllerID;
$class = $generator->modelClass;
$pks = $class::primaryKey();
$urlParams = [];
foreach ($pks as $pk) {
    $urlParams[] = "$pk: model.$pk";
}
$urlParams = implode(', ', $urlParams);

$viewChunks = array_chunk($views, 2);
?>
<script setup>
import { router } from '@inertiajs/vue3';
const { yiiUrl } = window;

const props = defineProps({
    model: Object,
});

function deleteModel(model){
    confirm('Are you sure you want to delete this item?').then(() => {
        axios.post(yiiUrl.post('<?= $baseRoute ?>/delete', {<?= $urlParams ?>})).then(()=>{
            router.visit(yiiUrl('<?= $baseRoute ?>/index'));
        });
    });
}
</script>
<template>
    <v-container fluid>
        <v-row dense>
            <v-col cols="12">
                <p>
                    <Link :href="yiiUrl.home" class="text-decoration-none"><v-icon>mdi-home</v-icon></Link> /
                    <Link :href="yiiUrl('<?= $baseRoute ?>')" >List <?= $modelName ?></Link> /
                    <span >View <?= $modelName ?></span>
                </p>           
            </v-col>
            <v-col cols="12">
                <v-card>
                    <v-toolbar density="compact">
                        <v-btn density="compact" icon="mdi-arrow-left" :to="yiiUrl('<?= $baseRoute ?>/index')"></v-btn>
                        <v-toolbar-title>View <?= $modelName ?></v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-btn density="compact" icon="mdi-pencil" :to="yiiUrl('<?= $baseRoute ?>/update', {id: model.id})"></v-btn>
                        <v-btn density="compact" icon="mdi-plus" :to="yiiUrl('<?= $baseRoute ?>/create')"></v-btn>
                        <v-btn density="compact" icon="mdi-delete" @click="deleteModel(model)"></v-btn>
                    </v-toolbar>
                    <v-card-text>
<?php foreach($viewChunks as $parts): ?>
                        <v-row>
<?php foreach($parts as $part): ?>
                            <v-col xl="6" md="6" sm="6" cols="12">
                                <v-row>
                                    <v-col cols="4" class="font-bold"><?= $part['label'] ?></v-col>
                                    <v-col cols="8"> {{model.<?= $part['field'] ?>}} </v-col>
                                </v-row>
                            </v-col>
<?php endforeach; ?>
                        </v-row>
<?php endforeach; ?>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </v-container>
</template>