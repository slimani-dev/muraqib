<script setup lang="ts">
import { Icon } from '@iconify/vue';
import type { LayoutItem, WidgetColumns, WidgetRows } from '../layout';
import { useLayoutEditor } from './layoutEditor';

const props = defineProps<{
    item: LayoutItem;
    label: string;
    icon: string;
    /** Column span of the container in the middle zone; null in a side zone (no span controls). */
    maxColumns: WidgetColumns | null;
    /** Widgets only: shown as just this toolbar. */
    collapsed?: boolean;
}>();

const editor = useLayoutEditor();

const spans: WidgetColumns[] = [1, 2, 3];
const heights: WidgetRows[] = [1, 2, 3, 4];

const setColumns = (columns: WidgetColumns): void =>
    editor.update(props.item, { columns });
const setRows = (rows: WidgetRows): void => editor.update(props.item, { rows });
</script>

<template>
    <div
        class="flex flex-col rounded-xl border-2 border-dashed transition-colors"
        :class="[
            item.kind === 'widget'
                ? 'border-primary/30 hover:border-primary/60'
                : 'border-chart-4/40 hover:border-chart-4/70',
            { 'h-full': !collapsed },
        ]"
    >
        <div
            class="flex items-center gap-1.5 bg-muted/60 px-1.5 py-1 text-[11px]"
            :class="collapsed ? 'rounded-lg' : 'rounded-t-lg'"
        >
            <button
                type="button"
                class="layout-handle flex h-6 w-6 shrink-0 cursor-grab items-center justify-center rounded-md hover:bg-background active:cursor-grabbing"
                title="Drag to move"
            >
                <Icon icon="lucide:grip-vertical" class="h-3.5 w-3.5" />
            </button>
            <Icon
                :icon="`lucide:${icon}`"
                class="h-3.5 w-3.5 shrink-0 text-muted-foreground"
            />
            <span class="min-w-0 flex-1 truncate font-semibold">{{
                label
            }}</span>

            <!-- Column span (middle zone only) -->
            <div
                v-if="maxColumns !== null"
                class="flex shrink-0 overflow-hidden rounded-md border border-border/60"
                title="Columns"
            >
                <button
                    v-for="span in spans"
                    :key="span"
                    type="button"
                    :disabled="span > maxColumns"
                    class="h-6 w-6 cursor-pointer font-mono text-[10px] transition-colors disabled:cursor-not-allowed disabled:opacity-30"
                    :class="
                        (item.columns ?? 1) === span
                            ? 'bg-primary text-primary-foreground'
                            : 'hover:bg-background'
                    "
                    :title="`${span} column${span > 1 ? 's' : ''}`"
                    @click="setColumns(span)"
                >
                    {{ span }}
                </button>
            </div>

            <!-- Height in grid rows (middle zone only) -->
            <select
                v-if="maxColumns !== null"
                :value="item.rows ?? 1"
                title="Height (rows)"
                class="h-6 shrink-0 cursor-pointer rounded-md border border-border/60 bg-transparent px-1 text-[10px]"
                @change="
                    setRows(
                        Number(
                            ($event.target as HTMLSelectElement).value,
                        ) as WidgetRows,
                    )
                "
            >
                <option v-for="rows in heights" :key="rows" :value="rows">
                    {{ rows }} row{{ rows > 1 ? 's' : '' }}
                </option>
            </select>

            <button
                v-if="item.kind === 'widget'"
                type="button"
                class="flex h-6 w-6 shrink-0 cursor-pointer items-center justify-center rounded-md text-muted-foreground hover:bg-background hover:text-foreground"
                :title="collapsed ? 'Expand' : 'Collapse'"
                :aria-expanded="!collapsed"
                @click="editor.toggleCollapsed(item.id)"
            >
                <Icon
                    :icon="
                        collapsed
                            ? 'lucide:chevrons-up-down'
                            : 'lucide:chevrons-down-up'
                    "
                    class="h-3.5 w-3.5"
                />
            </button>
            <button
                type="button"
                class="flex h-6 w-6 shrink-0 cursor-pointer items-center justify-center rounded-md text-muted-foreground hover:bg-destructive/15 hover:text-destructive"
                title="Remove"
                @click="editor.remove(item)"
            >
                <Icon icon="lucide:x" class="h-3.5 w-3.5" />
            </button>
        </div>

        <!-- Widgets are shown but not clickable while editing, so a drag never opens a link -->
        <div
            v-show="!collapsed"
            class="min-h-0 flex-1 p-1.5"
            :class="{
                'pointer-events-none select-none': item.kind === 'widget',
            }"
        >
            <slot />
        </div>
    </div>
</template>
