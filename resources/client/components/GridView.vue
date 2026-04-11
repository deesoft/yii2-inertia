<script setup>
import { URL } from '@/composables/url';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    data: { type: [Object,Array], required: true },
    columns: { type: Array, required: true },
    rowClass: { type: [Function, Array, String] },
    noFilter: { type: Boolean, default: false },
    itemsName: { type: String, default: 'items' },
    metaName: { type: String, default: 'meta' },
    linksName: { type: String, default: 'links' },
    valign: { type: String },
    title: { type: String },
    noSearch: { type: Boolean, default: false },
    striped: { type: String },
    sizes: {
        type: Array,
        default() {
            return [5, 10, 20, 25, 30, 40, 50, 100, 500];
        }
    }
});
const q = ref(URL.params.q);

const items = computed(() => Array.isArray(props.data) ? props.data : (props.data[props.itemsName] ? props.data[props.itemsName] : []));
const meta = computed(() => props.data[props.metaName] ? props.data[props.metaName] : null);
const links = computed(() => props.data[props.linksName] ? props.data[props.linksName] : null);
const emit = defineEmits(['reload']);

watch(() => URL.params.q, (val) => {
    q.value = val;
});
function doReload(param) {
    URL.reload(param);
}
const sort = computed(() => {
    let s = URL.params.sort;
    if (s) {
        return s.split(',').map(v => (v.charAt(0) == '-' ? { key: v.substring(1), direction: 'desc' } : { key: v, direction: 'asc' }));
    }
    return [];
});

function calcRowClass(row, i) {
    if (props.rowClass instanceof Function) {
        return props.rowClass(row, i);
    }
    return props.rowClass;
}
function lineNo(i) {
    if (meta.value) {
        return (meta.value.currentPage - 1) * meta.value.perPage + i + 1;
    }
    return i + 1;
}

function sorting(key) {
    if (!key) {
        return;
    }
    const result = [];
    let part = sort.value.find(v => v.key == key);
    if (part && part.direction == 'asc') {
        result.push('-' + key);
    } else if (!part) {
        result.push(key);
    }

    sort.value.forEach(v => {
        if (v.key != key) {
            result.push((v.direction == 'asc' ? '' : '-') + v.key);
        }
    });

    doReload({ sort: result.join(',') });
}

function sortedState(key) {
    if (key) {
        const v = sort.value.find(v => v.key == key)
        return v ? v.direction : false;
    }
    return false;
}
const pageNumber = computed({
    get() {
        return meta.value ? meta.value.currentPage : null;
    },
    set(val) {
        doReload({ 'page': val });
    }
});
const pageSize = computed({
    get() {
        return meta.value ? meta.value.perPage : null;
    },
    set(val) {
        doReload({ 'per-page': val });
    }
});

function doFilter(field) {
    return value => doReload({ [field]: value });
}
</script>
<template>
    <v-card>
        <v-toolbar density="compact">
            <slot name="prepend-toolbar"></slot>
            <v-toolbar-title v-if="title">{{ title }}</v-toolbar-title>
            <v-spacer></v-spacer>
            <slot name="append-toolbar"></slot>
            <v-toolbar-items v-if="!noSearch">
                <slot name="search" v-bind="{reload: doReload, params: URL.params}">
                    <v-text-field density="compact" v-model="q" hide-details append-inner-icon="mdi-magnify" style="min-width: 350px;"
                        @click:append-inner="doReload({ q: q })" @change="doReload({ q: q })" single-line></v-text-field>
                </slot>
            </v-toolbar-items>
        </v-toolbar>
        <v-card-text>
            <v-table density="compact" :striped="striped" class="grid-view">
                <thead>
                    <tr>
                        <th class="pb-1" :valign="valign" v-for="(column, idx) in columns" :class="column.headerClass"
                            :data-field="column.field" :key="idx" :width="column.width">
                            <span v-if="column.sort" class="cursor-pointer" @click="sorting(column.sort)">
                                <slot :name="'h-' + column.field" v-bind="{ column, sortedState:sortedState(column.sort) }">{{ column.title || column.field }}
                                </slot>
                                <v-chip size="x-small" v-if="sortedState(column.sort)"
                                    :color="sortedState(column.sort) == 'asc' ? 'green' : 'blue'">
                                    <v-icon end v-if="sortedState(column.sort) == 'asc'">mdi-arrow-up</v-icon>
                                    <v-icon end v-else>mdi-arrow-down</v-icon>
                                </v-chip>
                            </span>
                            <span v-else>
                                <slot :name="'h-' + column.field" v-bind="{column, sortedState:false}">{{ column.title || column.field }} </slot>
                            </span>
                        </th>
                    </tr>
                    <tr v-if="!noFilter">
                        <th :valign="valign" v-for="(column, idx) in columns" :class="column.headerClass"
                            :data-field="column.field" :key="idx">
                            <template v-if="column.filter !== false">
                                <slot :name="'filter-' + column.field"
                                    v-bind="{ column, value: URL.params[column.filter || column.field], doFilter: doFilter(column.filter || column.field), doFilters: doReload }">
                                    <v-select v-if="column.filterItems && Array.isArray(column.filterItems)"
                                        :items="column.filterItems" density="compact" hide-details
                                        v-model="URL.params[column.filter || column.field]"
                                        @update:modelValue="doReload({ [column.filter || column.field]: $event })"></v-select>
                                    <v-text-field v-else density="compact" hide-details flat
                                        v-model="URL.params[column.filter || column.field]"
                                        @change="doReload({ [column.filter || column.field]: $event.target.value })"></v-text-field>
                                </slot>
                            </template>
                        </th>
                    </tr>
                </thead>
                <tbody class="text-caption">
                    <tr v-if="items.length == 0">
                        <td :valign="valign" :colspan="columns.length">No Data Available</td>
                    </tr>
                    <tr v-for="(row, i) in items" :key="i" :class="calcRowClass(row, i)">
                        <td :valign="valign" v-for="(column, idx) in columns" :key="idx" :class="column.dataClass"
                            :style="{ 'text-align': column.align || 'left' }">
                            <slot :name="'d-' + column.field" v-bind="{ row, index: i, line: lineNo(i) }">
                                {{ column.formatter ? column.formatter(row[column.field], row, i, idx) : row[column.field] }}
                            </slot>
                        </td>
                    </tr>
                </tbody>
            </v-table>
        </v-card-text>
        <v-card-actions v-if="meta || (links && links.length)">
            <v-btn-group v-if="links && links.length">
                <v-btn v-for="(link, i) in links" :key="i" :to="link.href" :disabled="link.active">
                    {{ link.label }}
                </v-btn>
            </v-btn-group>
            <v-pagination v-else v-model="pageNumber" :length="meta.pageCount" density="compact"
                :total-visible="7"></v-pagination>
            <v-spacer></v-spacer>
            <v-select v-if="sizes && sizes.length" v-model="pageSize" style="max-width: 100px;" hide-details
                density="compact" variant="solo" :items="sizes"></v-select>
        </v-card-actions>
    </v-card>
</template>