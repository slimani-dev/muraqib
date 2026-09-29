<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { computed, ref } from 'vue';
import { VueDraggable } from 'vue-draggable-plus';
import type { LayoutItem } from '../layout';
import { newSection, newTabs, newWidgetItem } from '../layout';
import type { WidgetCategory, WidgetDefinition } from '../widgets/registry';
import { widgetCategories } from '../widgets/registry';

const props = defineProps<{
    /** Widgets not yet on this page. */
    widgets: WidgetDefinition[];
}>();

const emit = defineEmits<{
    /** Add to the end of the middle zone (the click alternative to dragging). */
    add: [item: LayoutItem];
    close: [];
}>();

type CatalogEntry = {
    kind: 'widget' | 'section' | 'tabs';
    key: string;
    title: string;
    icon: string;
    hint: string;
    definition?: WidgetDefinition;
};

const search = ref('');
const category = ref<WidgetCategory | 'all'>('all');

const blocks: CatalogEntry[] = [
    {
        kind: 'section',
        key: 'section',
        title: 'Section',
        icon: 'layout-list',
        hint: 'A titled group of widgets',
    },
    {
        kind: 'tabs',
        key: 'tabs',
        title: 'Tabbed section',
        icon: 'panel-top',
        hint: 'Tabs, each holding widgets or sections',
    },
];

const entries = computed<CatalogEntry[]>(() => {
    const query = search.value.trim().toLowerCase();

    return props.widgets
        .filter(
            (definition) =>
                category.value === 'all' ||
                definition.category === category.value,
        )
        .filter(
            (definition) =>
                !query || definition.title.toLowerCase().includes(query),
        )
        .sort((a, b) => a.title.localeCompare(b.title))
        .map((definition) => ({
            kind: 'widget',
            key: definition.id,
            title: definition.title,
            icon: definition.icon,
            hint:
                widgetCategories.find(
                    (option) => option.value === definition.category,
                )?.label ?? '',
            definition,
        }));
});

/** What a catalog entry becomes when it's dropped on the dashboard. */
const toLayoutItem = (entry: CatalogEntry): LayoutItem => {
    if (entry.kind === 'section') {
        return newSection();
    }

    if (entry.kind === 'tabs') {
        return newTabs();
    }

    return newWidgetItem(entry.key, 1);
};

const catalogGroup = { name: 'layout', pull: 'clone' as const, put: false };
</script>

<template>
    <!-- A plain container in the page (no overlay): drag from here onto any zone -->
    <div
        class="flex flex-col gap-3 rounded-xl border bg-card/80 backdrop-blur-xl"
    >
        <div class="flex items-start justify-between gap-2 border-b px-4 py-3">
            <div>
                <h2 class="text-sm font-semibold">Widgets</h2>
                <p class="text-xs text-muted-foreground">
                    Drag onto the dashboard, or press + to add to the middle.
                </p>
            </div>
            <button
                type="button"
                class="flex h-7 w-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-muted-foreground hover:bg-muted hover:text-foreground"
                title="Hide widgets"
                @click="emit('close')"
            >
                <Icon icon="lucide:x" class="h-4 w-4" />
            </button>
        </div>

        <div class="space-y-3 px-4 pb-4">
            <p
                class="text-[11px] font-bold tracking-wider text-muted-foreground uppercase"
            >
                Layout
            </p>
            <VueDraggable
                :model-value="blocks"
                :group="catalogGroup"
                :sort="false"
                :clone="toLayoutItem"
                class="space-y-1.5"
            >
                <div
                    v-for="entry in blocks"
                    :key="entry.key"
                    :data-kind="entry.kind"
                    class="flex cursor-grab items-center gap-2.5 rounded-lg border border-dashed border-chart-4/40 bg-card px-2.5 py-2 active:cursor-grabbing"
                >
                    <Icon
                        :icon="`lucide:${entry.icon}`"
                        class="h-4 w-4 shrink-0 text-chart-4"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="text-[13px] font-semibold">
                            {{ entry.title }}
                        </p>
                        <p class="truncate text-[11px] text-muted-foreground">
                            {{ entry.hint }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="flex h-6 w-6 cursor-pointer items-center justify-center rounded-md hover:bg-muted"
                        title="Add to the middle zone"
                        @click="emit('add', toLayoutItem(entry))"
                    >
                        <Icon icon="lucide:plus" class="h-3.5 w-3.5" />
                    </button>
                </div>
            </VueDraggable>

            <p
                class="pt-1 text-[11px] font-bold tracking-wider text-muted-foreground uppercase"
            >
                Widgets
            </p>
            <input
                v-model="search"
                type="search"
                placeholder="Search widgets"
                class="h-8 w-full rounded-lg border border-border/60 bg-background px-2.5 text-[13px] outline-none placeholder:text-muted-foreground focus:border-primary/60"
            />
            <div class="flex flex-wrap gap-1">
                <button
                    v-for="option in [
                        { value: 'all', label: 'All' },
                        ...widgetCategories,
                    ]"
                    :key="option.value"
                    type="button"
                    class="cursor-pointer rounded-full border px-2.5 py-0.5 text-[11px] font-medium transition-colors"
                    :class="
                        category === option.value
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border/60 text-muted-foreground hover:text-foreground'
                    "
                    @click="category = option.value as WidgetCategory | 'all'"
                >
                    {{ option.label }}
                </button>
            </div>

            <VueDraggable
                :model-value="entries"
                :group="catalogGroup"
                :sort="false"
                :clone="toLayoutItem"
                class="space-y-1.5"
            >
                <div
                    v-for="entry in entries"
                    :key="entry.key"
                    :data-kind="entry.kind"
                    class="flex cursor-grab items-center gap-2.5 rounded-lg border border-border/60 bg-card px-2.5 py-2 transition-colors hover:border-primary/50 active:cursor-grabbing"
                >
                    <Icon
                        :icon="`lucide:${entry.icon}`"
                        class="h-4 w-4 shrink-0 text-primary"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-[13px] font-semibold">
                            {{ entry.title }}
                        </p>
                        <p class="text-[11px] text-muted-foreground">
                            {{ entry.hint }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="flex h-6 w-6 cursor-pointer items-center justify-center rounded-md hover:bg-muted"
                        title="Add to the middle zone"
                        @click="emit('add', toLayoutItem(entry))"
                    >
                        <Icon icon="lucide:plus" class="h-3.5 w-3.5" />
                    </button>
                </div>
            </VueDraggable>
            <p
                v-if="!entries.length"
                class="py-4 text-center text-xs text-muted-foreground"
            >
                {{
                    widgets.length
                        ? 'No widgets match.'
                        : 'Every widget is already on this page.'
                }}
            </p>
        </div>
    </div>
</template>
