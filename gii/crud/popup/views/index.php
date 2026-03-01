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
    $urlParams[] = "$pk:row.$pk";
}
$urlParams = implode(', ', $urlParams);
?>
<script setup>
import { router } from "@inertiajs/vue3";
import {confirm} from "@/composables/global";
import FormDlg from './FormDlg.vue';
const {yiiUrl} = window;

const props = defineProps({
    data: Object,    
});
const formDlg = useTemplateRef('formDlg');
const columns = [
    {field:'no', title:'NO', filter: false},
<?php 
$count = 0;
foreach($views as $col):
$count++;
?>
    <?= ($count > 6 ? '// ':'') ?>{field: '<?= $col['field'] ?>', title: '<?= $col['label'] ?>' <?= $col['sort'] ? ", sort: '{$col['field']}'":'' ?>},
<?php endforeach; ?>
    {field:'action', title:'Action', filter: false},
];

function deleteRow(row){
    confirm('Are you sure you want to delete this item?').then(() => {
        axios.post(yiiUrl.post('<?= $baseRoute ?>/delete', {<?= $urlParams ?>})).then(()=>{
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
                <v-card>
                    <v-toolbar density="compact">
                        <v-btn density="compact" icon="mdi-reload" @click="router.reload()"></v-btn>
                        <v-toolbar-title><?= $modelName ?></v-toolbar-title>
                        <v-spacer></v-spacer>
                        <v-toolbar-items>
                            <QuerySearchText density="compact" style="min-width: 250px;"></QuerySearchText>
                        </v-toolbar-items>
                        <v-btn density="compact" icon="mdi-plus" @click="formDlg.open()"></v-btn>
                    </v-toolbar>
                    <v-divider/>
                    <GridView :data="data" :columns="columns" reload density="compact">
                        <template #d-no="{line}">{{ line }}</template>
                        <template #d-action="{row}">
                            <v-btn density="compact" size="small" icon="mdi-pencil" @click="formDlg.open(row)"></v-btn>
                            <v-btn density="compact" size="small" icon="mdi-delete" @click="deleteRow(row)"></v-btn>
                        </template>
                    </GridView>
                </v-card>
            </v-col>
        </v-row>
        <FormDlg ref="formDlg"></FormDlg>
    </v-container>
</template>