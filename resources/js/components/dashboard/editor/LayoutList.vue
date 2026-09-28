<script setup lang="ts">
import { VueDraggable } from 'vue-draggable-plus';
import type { LayoutItem, ListKind, WidgetColumns } from '../layout';
import { MIDDLE_ZONE_GRID_CLASS, nestedGridClass } from '../layout';
import { acceptsDrop, useLayoutEditor } from './layoutEditor';
import LayoutNode from './LayoutNode.vue';

const props = defineProps<{
    items: LayoutItem[];
    /** Where the list lives, which decides what can be dropped in it. */
    kind: ListKind;
    /** The object holding the list, and its key, so drops can replace it. */
    owner: Record<string, any>;
    ownerKey: string;
    /** Column span of the container in the middle zone; null in a side zone. */
    columns: WidgetColumns | null;
    /** The middle zone itself uses the page-level grid. */
    isZone?: boolean;
    emptyText?: string;
}>();

const editor = useLayoutEditor();

const gridClass = (): string => {
    if (props.columns === null) {
        return 'flex flex-col gap-2.5';
    }

    return props.isZone ? MIDDLE_ZONE_GRID_CLASS : nestedGridClass(props.columns);
};
</script>

<template>
    <div class="relative">
        <VueDraggable :model-value="items" :group="{ name: 'layout', pull: true, put: acceptsDrop(kind) }" :disabled="!editor.editing.value"
            handle=".layout-handle" :animation="200" ghost-class="layout-ghost" :delay="200" :delay-on-touch-only="true"
            :class="[gridClass(), editor.editing.value ? 'min-h-24 rounded-xl' : '']"
            @update:model-value="editor.replaceList(owner, ownerKey, $event)">
            <LayoutNode v-for="item in items" :key="item.id" :item="item" :parent-columns="columns" />
        </VueDraggable>
        <div v-if="editor.editing.value && !items.length"
            class="pointer-events-none absolute inset-0 flex items-center justify-center rounded-xl border-2 border-dashed border-border/60 text-xs text-muted-foreground">
            {{ emptyText ?? 'Drop widgets here' }}
        </div>
    </div>
</template>
