<script setup lang="ts">
import type { SectionItem, WidgetColumns } from '../layout';
import SectionLabel from '../SectionLabel.vue';
import { useLayoutEditor } from './layoutEditor';
import LayoutList from './LayoutList.vue';

const props = defineProps<{
    item: SectionItem;
    /** Columns the section spans in the middle zone; null in a side zone. */
    columns: WidgetColumns | null;
}>();

const editor = useLayoutEditor();

const rename = (event: Event): void => {
    const title = (event.target as HTMLInputElement).value.trim();
    editor.update(props.item, { title: title || 'Section' });
};
</script>

<template>
    <section class="flex flex-col">
        <input v-if="editor.editing.value" :value="item.title" maxlength="60" aria-label="Section title"
            class="mb-2 w-full rounded-md border border-border/60 bg-background/40 px-2 py-1 text-[0.72rem] font-bold tracking-[0.11em] uppercase outline-none focus:border-primary/60"
            @change="rename" />
        <SectionLabel v-else icon="layout-list" :text="item.title" />
        <LayoutList :items="item.items" kind="section" :owner="item" owner-key="items" :columns="columns" empty-text="Drop widgets into this section" />
    </section>
</template>
