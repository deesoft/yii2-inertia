<?php

use yii\helpers\Html;
use yii\helpers\StringHelper;

/** @var yii\web\View $this */
/** @var dee\inertia\gii\crud\Generator $generator*/

$modelClass = StringHelper::basename($generator->modelClass);
$baseRoute = $generator->controllerID;

$viewChunks = array_chunk($views, (count($views) + 1) / 2);
?>
<script setup>
const {yiiUrl} = window;

const props = defineProps({
    model: Object,    
});
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
                <form >
                    <v-card>
                        <v-toolbar density="default">
                            <v-btn density="compact" icon="mdi-arrow-left" @click="yiiUrl.back()"></v-btn>
                            <v-toolbar-title >View <?= $modelName ?></v-toolbar-title>
                        </v-toolbar> 
                        <v-card-text>
                            <v-row>
<?php foreach($viewChunks as $parts): ?>
                                <v-col xl="6" md="6" sm="6" cols="12">
<?php if($generator->viewList): ?>
                                    <v-list density="compact">
<?php foreach($parts as $part): ?>
                                        <v-list-item>
                                            <v-list-item-title class="font-bold"><?= $part['label'] ?></v-list-item-title>
                                            <v-list-item-subtitle> {{model.<?= $part['field'] ?>}} </v-list-item-subtitle>
                                        </v-list-item>
<?php endforeach; ?>
                                    </v-list>
<?php else: ?>
<?php foreach($parts as $part): ?>
                                    <v-row>
                                        <v-col cols="4" class="font-bold"><?= $part['label'] ?></v-col>
                                        <v-col cols="8"> {{model.<?= $part['field'] ?>}} </v-col>
                                    </v-row>
<?php endforeach; ?>
<?php endif; ?>
                                </v-col>
<?php endforeach; ?>
                            </v-row>
                        </v-card-text>
                    </v-card>
                </form>
            </v-col>
        </v-row>
    </v-container>
</template>