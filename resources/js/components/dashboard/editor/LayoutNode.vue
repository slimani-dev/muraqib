<script setup lang="ts">
import { computed } from 'vue';
import type { LayoutItem, WidgetColumns } from '../layout';
import { middleZoneItemClass, modeForColumns } from '../layout';
import EditFrame from './EditFrame.vue';
import { useLayoutEditor } from './layoutEditor';
import SectionBlock from './SectionBlock.vue';
import TabsBlock from './TabsBlock.vue';
import WidgetSlot from './WidgetSlot.vue';

const props = defineProps<{
    item: LayoutItem;
    /** Column span of the container in the middle zone; null in a side zone. */
    parentColumns: WidgetColumns | null;
}>();

const editor = useLayoutEditor();

/** The columns this item actually gets: its own span, capped by its container. */
const columns = computed<WidgetColumns | null>(() => props.parentColumns === null
    ? null
    : Math.min(props.item.columns ?? 1, props.parentColumns) as WidgetColumns);

/** A collapsed widget is only its toolbar, so it drops its row height too. */
const collapsed = computed(() => editor.editing.value && props.item.kind === 'widget' && editor.isCollapsed(props.item.id));

const spanClass = computed(() => columns.value === null ? '' : middleZoneItemClass(columns.value, collapsed.value ? 1 : props.item.rows));

const definition = computed(() => {
    const item = props.item;

    return item.kind === 'widget' ? editor.available.value.find((candidate) => candidate.id === item.widget) : undefined;
});

const label = computed(() => {
    if (props.item.kind === 'widget') {
        return definition.value?.title ?? 'Unavailable widget';
    }

    return props.item.kind === 'section' ? props.item.title : props.item.title || 'Tabbed section';
});
</script>

<template>
    <div :data-kind="item.kind" :data-id="item.id" :class="spanClass">
        <EditFrame v-if="editor.editing.value" :item="item" :label="label" :max-columns="parentColumns" :collapsed="collapsed"
            :icon="item.kind === 'widget' ? definition?.icon ?? 'puzzle' : item.kind === 'section' ? 'layout-list' : 'panel-top'">
            <WidgetSlot v-if="item.kind === 'widget'" :item="item" :definition="definition" :mode="columns === null ? 'mobile' : modeForColumns(columns)" />
            <SectionBlock v-else-if="item.kind === 'section'" :item="item" :columns="columns" />
            <TabsBlock v-else :item="item" :columns="columns" />
        </EditFrame>
        <template v-else>
            <WidgetSlot v-if="item.kind === 'widget'" :item="item" :definition="definition" :mode="columns === null ? 'mobile' : modeForColumns(columns)" />
            <SectionBlock v-else-if="item.kind === 'section'" :item="item" :columns="columns" />
            <TabsBlock v-else :item="item" :columns="columns" />
        </template>
    </div>
</template>
