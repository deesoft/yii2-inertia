<?php

use yii\helpers\StringHelper;

/** @var yii\web\View $this */
/** @var dee\inertia\gii\crud\Generator $generator*/

$modelClass = StringHelper::basename($generator->modelClass);
$baseRoute = $generator->controllerID;
$class = $generator->modelClass;
$pks = $class::primaryKey();
$urlParams = [];
foreach ($pks as $pk) {
    $urlParams[] = "$pk: row.$pk";
}
$urlParams = implode(', ', $urlParams);
?>
<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { confirm } from "@/composables/global";
import { TModel } from "./type";
const { yiiUrl } = window;

const props = defineProps<{
    data: TDataProvider<TModel>,
}>();
const columns = [
    {field: 'no', title: 'NO', filter: false, width: 60},
<?php 
$count = 0;
foreach($views as $col):
$count++;
?>
    <?= ($count > 6 ? '// ':'') ?>{field: '<?= $col['field'] ?>', title: '<?= $col['label'] ?>' <?= $col['sort'] ? ", sort: '{$col['field']}'":'' ?>, filter: true, width: <?= $col['width'] ?>},
<?php endforeach; ?>
    {field: 'action', title: 'Action', filter: false, width: 100},
];

function deleteRow(row: TModel){
    confirm('Are you sure you want to delete this item?').then(() => {
        useHttp().post(yiiUrl.post('<?= $baseRoute ?>/delete', {<?= $urlParams ?>})).then(()=>{
            router.reload();
        });
    });
}
</script>
<template>
    <v-container fluid>
        <v-row density="compact">
            <v-col cols="12">
                <p>
                    <Link :href="yiiUrl.home" class="text-decoration-none"><v-icon>mdi-home</v-icon></Link> /
                    <span >List <?= $modelName ?></span>
                </p>
            </v-col>
            <v-col cols="12">
                <GridView :data="data" :columns="columns" title="<?= $modelName ?>">
                    <template #prepend-toolbar>
                        <v-btn density="compact" icon="mdi-reload" @click="router.reload()"></v-btn>
                        <v-btn density="compact" icon="mdi-plus" :to="yiiUrl('<?= $baseRoute ?>/create')"></v-btn>
                    </template>
                    <template #d-no="{line}">{{ line }}</template>
                    <template #d-action="{row}">
                        <v-btn density="compact" size="small" icon="mdi-eye" :to="yiiUrl('<?= $baseRoute ?>/view', {<?= $urlParams ?>})"></v-btn>
                        <v-btn density="compact" size="small" icon="mdi-pencil" :to="yiiUrl('<?= $baseRoute ?>/update', {<?= $urlParams ?>})"></v-btn>
                        <v-btn density="compact" size="small" icon="mdi-delete" @click="deleteRow(row)"></v-btn>
                    </template>
                </GridView>
            </v-col>
        </v-row>
    </v-container>
</template>