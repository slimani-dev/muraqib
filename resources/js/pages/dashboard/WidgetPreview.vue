<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { useElementSize } from '@vueuse/core';
import { computed, onMounted, ref, useTemplateRef, watch } from 'vue';
import type { WidgetColumns } from '@/components/dashboard/layout';
import {
    DASHBOARD_GRID_CLASS,
    defaultMiddleZoneItem,
    MIDDLE_ZONE_CONTAINER_CLASS,
    MIDDLE_ZONE_GRID_CLASS,
    middleZoneItemClass,
    modeForColumns,
} from '@/components/dashboard/layout';
import type { WidgetDefinition } from '@/components/dashboard/widgets/registry';
import { widgetsFor } from '@/components/dashboard/widgets/registry';
import type { WidgetMode } from '@/components/dashboard/widgets/widgetMode';
import { resolveWidgetLayout, WIDGET_MEDIUM_MIN_WIDTH } from '@/components/dashboard/widgets/widgetMode';
import { Button } from '@/components/ui/button';
import { NativeSelect, NativeSelectOption } from '@/components/ui/native-select';
import { dashboard } from '@/routes';
import type { Team } from '@/types';

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: layoutProps.currentTeam ? dashboard(layoutProps.currentTeam.slug) : '/',
            },
            { title: 'Widget preview', href: '#' },
        ],
    }),
});

const page = usePage();

// The page only carries Netdata's cached static info; fetch live stats once so the preview has charts.
const liveNetdata = ref<any[] | null>(null);
onMounted(async () => {
    try {
        const response = await fetch('/api/netdata', { headers: { Accept: 'application/json' } });

        if (response.ok) {
            liveNetdata.value = (await response.json()).servers ?? null;
        }
    } catch {
        // The preview falls back to the cached Netdata data.
    }
});

const dashboardProps = computed(() => ({
    ...page.props,
    netdata: liveNetdata.value ?? page.props.netdata,
}));

const widgets = computed(() => widgetsFor(dashboardProps.value));
const selectedWidgetId = ref(widgets.value[0]?.id ?? '');
const selectedWidget = computed(() => widgets.value.find((widget) => widget.id === selectedWidgetId.value));
const widgetProps = computed(() => selectedWidget.value?.props(dashboardProps.value) ?? {});

type Placement = WidgetColumns | 'side';

const placements: { value: Placement; label: string; hint: string }[] = [
    { value: 3, label: 'Middle · 3 cols', hint: 'Desktop mode across the whole middle zone' },
    { value: 2, label: 'Middle · 2 cols', hint: 'Desktop mode across two thirds of the middle zone' },
    { value: 1, label: 'Middle · 1 col', hint: 'One column is the mobile version' },
    { value: 'side', label: 'Side zone', hint: 'Left/right zones always use the mobile version' },
];

/** Where the widget sits on the default dashboard; widgets not in the middle zone live in a side zone. */
const defaultPlacement = (widget: WidgetDefinition | undefined): Placement =>
    defaultMiddleZoneItem(widget)?.columns ?? 'side';

const placement = ref<Placement>(defaultPlacement(selectedWidget.value));

watch(() => selectedWidget.value?.id, () => {
    placement.value = defaultPlacement(selectedWidget.value);
});

/** 2 and 3 columns only exist on a desktop-width dashboard, so picking one switches the screen to Desktop. */
const choosePlacement = (value: Placement): void => {
    placement.value = value;

    if (value === 2 || value === 3) {
        screen.value = 'desktop';
    }
};

const mode = computed<WidgetMode>(() => (placement.value === 'side' ? 'mobile' : modeForColumns(placement.value)));
const middleColumns = computed<WidgetColumns>(() => (placement.value === 'side' ? 3 : placement.value));
const middleFillerColumns = computed<WidgetColumns | 0>(() => (3 - middleColumns.value) as WidgetColumns | 0);

type Screen = 'desktop' | 'phone';
const screen = ref<Screen>('desktop');
const screens: { value: Screen; label: string }[] = [
    { value: 'desktop', label: 'Desktop' },
    { value: 'phone', label: 'Phone (390px)' },
];

const slot = useTemplateRef<HTMLElement>('slot');
const { width: slotWidth } = useElementSize(slot);

const renderedLayout = computed<WidgetMode>(() => resolveWidgetLayout(mode.value, slotWidth.value));

const layoutLabels: Record<WidgetMode, string> = {
    desktop: '3-col (desktop)',
    medium: '2-col (medium)',
    mobile: '1-col (mobile)',
};

const layoutColors: Record<WidgetMode, string> = {
    desktop: 'text-primary',
    medium: 'text-chart-3',
    mobile: 'text-chart-4',
};

const zonePlaceholderClass =
    'flex h-40 items-center justify-center rounded-xl border border-dashed text-xs text-muted-foreground';
</script>

<template>
    <Head title="Widget preview" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-end gap-4 rounded-xl border bg-card/70 p-4">
            <label class="flex flex-col gap-1.5 text-xs font-medium text-muted-foreground">
                Widget
                <NativeSelect v-model="selectedWidgetId" class="min-w-56">
                    <NativeSelectOption v-for="widget in widgets" :key="widget.id" :value="widget.id">
                        {{ widget.title }}
                    </NativeSelectOption>
                </NativeSelect>
            </label>

            <div class="flex flex-col gap-1.5 text-xs font-medium text-muted-foreground">
                Placement
                <div class="flex flex-wrap gap-1">
                    <Button v-for="option in placements" :key="option.value" size="sm" :title="option.hint"
                        :variant="placement === option.value ? 'default' : 'outline'" @click="choosePlacement(option.value)">
                        {{ option.label }}
                    </Button>
                </div>
            </div>

            <div class="flex flex-col gap-1.5 text-xs font-medium text-muted-foreground">
                Screen
                <div class="flex gap-1">
                    <Button v-for="option in screens" :key="option.value" size="sm"
                        :variant="screen === option.value ? 'default' : 'outline'" @click="screen = option.value">
                        {{ option.label }}
                    </Button>
                </div>
            </div>

            <div class="ml-auto flex flex-col items-end gap-1 font-mono text-xs text-muted-foreground">
                <span>{{ Math.round(slotWidth) }}px wide</span>
                <span>
                    renders as
                    <span class="font-semibold" :class="layoutColors[renderedLayout]">
                        {{ layoutLabels[renderedLayout] }}
                    </span>
                </span>
            </div>
        </div>

        <p class="text-xs text-muted-foreground">
            <template v-if="screen === 'desktop'">
                The widget sits in the real dashboard columns at this window size. The middle zone is a 3-column grid
                (one column below 48rem): 3 columns use the desktop variation, 2 columns the medium one and 1 column the
                mobile one. Each falls back to a narrower variation when it doesn't fit. Resize the window to try other widths.
            </template>
            <template v-else>
                A 390px phone screen. Every widget falls back to its mobile variation below {{ WIDGET_MEDIUM_MIN_WIDTH }}px.
            </template>
        </p>

        <div v-if="screen === 'desktop'" :class="DASHBOARD_GRID_CLASS">
            <aside>
                <div v-if="placement === 'side'" ref="slot">
                    <component :is="selectedWidget.component" v-if="selectedWidget" v-bind="widgetProps" :mode="mode" />
                </div>
                <div v-else :class="zonePlaceholderClass">Left zone</div>
            </aside>

            <div :class="MIDDLE_ZONE_CONTAINER_CLASS">
                <div v-if="placement !== 'side'" :class="MIDDLE_ZONE_GRID_CLASS">
                    <div ref="slot" :class="middleZoneItemClass(middleColumns)">
                        <component :is="selectedWidget.component" v-if="selectedWidget" v-bind="widgetProps" :mode="mode" />
                    </div>
                    <div v-if="middleFillerColumns" :class="[zonePlaceholderClass, 'h-auto min-h-40', middleZoneItemClass(middleFillerColumns)]">
                        {{ middleFillerColumns }} free column{{ middleFillerColumns === 1 ? '' : 's' }}
                    </div>
                </div>
                <div v-else :class="zonePlaceholderClass">Middle zone</div>
            </div>

            <aside>
                <div :class="zonePlaceholderClass">Right zone</div>
            </aside>
        </div>

        <div v-else class="mx-auto w-[390px] max-w-full rounded-[2rem] border bg-background p-3 shadow-xl">
            <div ref="slot">
                <component :is="selectedWidget.component" v-if="selectedWidget" v-bind="widgetProps" :mode="mode" />
            </div>
        </div>
    </div>
</template>
