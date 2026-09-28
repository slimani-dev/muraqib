<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { computed, ref, watch } from 'vue';
import type { TabsItem, WidgetColumns } from '../layout';
import { useLayoutEditor } from './layoutEditor';
import LayoutList from './LayoutList.vue';

const props = defineProps<{
    item: TabsItem;
    /** Columns the tabbed section spans in the middle zone; null in a side zone. */
    columns: WidgetColumns | null;
}>();

const editor = useLayoutEditor();

/** Opens on the default tab; switching tabs is not saved. */
const activeId = ref(props.item.default_tab);
const active = computed(() => props.item.tabs.find((tab) => tab.id === activeId.value) ?? props.item.tabs[0]);

watch(() => props.item.tabs.map((tab) => tab.id), (ids) => {
    if (!ids.includes(activeId.value)) {
        activeId.value = props.item.default_tab;
    }
});

const rename = (event: Event): void => {
    const title = (event.target as HTMLInputElement).value.trim();
    editor.update(active.value, { title: title || 'Tab' });
};

const renameSection = (event: Event): void => {
    const title = (event.target as HTMLInputElement).value.trim();
    editor.update(props.item, { title: title || undefined });
};

const addTab = (): void => {
    editor.addTab(props.item);
    activeId.value = props.item.tabs[props.item.tabs.length - 1].id;
};
</script>

<template>
    <section class="flex flex-col">
        <div class="mb-2.5 flex flex-wrap items-center gap-2">
            <input v-if="editor.editing.value" :value="item.title ?? ''" maxlength="60" placeholder="Title (optional)" aria-label="Tabbed section title"
                class="w-40 rounded-md border border-border/60 bg-background/40 px-2 py-1 text-[0.72rem] font-bold tracking-[0.11em] uppercase outline-none focus:border-primary/60"
                @change="renameSection" />
            <span v-else-if="item.title" class="text-[0.72rem] font-bold tracking-[0.11em] text-muted-foreground uppercase">{{ item.title }}</span>

            <div class="flex flex-wrap items-center gap-1 rounded-lg bg-muted p-0.5 text-[11px] font-semibold">
                <button v-for="tab in item.tabs" :key="tab.id" type="button"
                    class="flex cursor-pointer items-center gap-1 rounded-md px-2.5 py-1 transition-colors"
                    :class="tab.id === active.id ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                    @click="activeId = tab.id">
                    <Icon v-if="editor.editing.value && tab.id === item.default_tab" icon="lucide:star" class="h-3 w-3 text-chart-4" title="Default tab" />
                    {{ tab.title }}
                </button>
                <button v-if="editor.editing.value && item.tabs.length < 12" type="button" class="flex cursor-pointer items-center rounded-md px-1.5 py-1 text-muted-foreground hover:text-foreground"
                    title="Add tab" @click="addTab">
                    <Icon icon="lucide:plus" class="h-3.5 w-3.5" />
                </button>
            </div>

            <!-- Edit the active tab -->
            <div v-if="editor.editing.value" class="flex items-center gap-1 text-[11px]">
                <input :value="active.title" maxlength="60" aria-label="Tab title"
                    class="w-28 rounded-md border border-border/60 bg-background/40 px-2 py-1 outline-none focus:border-primary/60" @change="rename" />
                <button type="button" class="flex h-6 cursor-pointer items-center gap-1 rounded-md px-1.5 hover:bg-muted disabled:cursor-default disabled:opacity-40"
                    :disabled="item.default_tab === active.id" title="Open this tab by default" @click="editor.update(item, { default_tab: active.id })">
                    <Icon icon="lucide:star" class="h-3 w-3" /> Default
                </button>
                <button type="button" class="flex h-6 w-6 cursor-pointer items-center justify-center rounded-md text-muted-foreground hover:bg-destructive/15 hover:text-destructive disabled:cursor-default disabled:opacity-40"
                    :disabled="item.tabs.length <= 1" title="Delete this tab" @click="editor.removeTab(item, active.id)">
                    <Icon icon="lucide:trash-2" class="h-3 w-3" />
                </button>
            </div>
        </div>

        <LayoutList :key="active.id" :items="active.items" kind="tab" :owner="active" owner-key="items" :columns="columns" empty-text="Drop widgets or sections into this tab" />
    </section>
</template>
