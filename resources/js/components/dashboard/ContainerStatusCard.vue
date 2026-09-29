<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { computed } from 'vue';
import SectionLabel from './SectionLabel.vue';
import type { WidgetMode } from './widgets/widgetMode';
import WidgetShell from './widgets/WidgetShell.vue';

/** A container from the dashboard's `containers` prop (synced from Portainer). */
type Container = {
    id: number;
    name: string;
    display_name: string | null;
    state: string;
    status: string | null;
    update_status: string | null;
    url: string | null;
    icon: string | null;
    stack_name: string | null;
};

const props = withDefaults(
    defineProps<{ containers?: Container[]; mode?: WidgetMode }>(),
    {
        containers: () => [],
        mode: 'desktop',
    },
);

type Health = {
    rank: number;
    label: string;
    dot: string;
    text: string;
    pulse: boolean;
};

/** What needs attention first: stopped, unhealthy, then updates, then healthy. */
const health = (container: Container): Health => {
    const status = (container.status ?? '').toLowerCase();

    if (container.state !== 'running') {
        return {
            rank: 0,
            label: container.state === 'paused' ? 'Paused' : 'Stopped',
            dot: 'bg-destructive',
            text: 'text-destructive',
            pulse: false,
        };
    }

    if (status.includes('(unhealthy)')) {
        return {
            rank: 1,
            label: 'Unhealthy',
            dot: 'bg-destructive',
            text: 'text-destructive',
            pulse: true,
        };
    }

    if (status.includes('health: starting')) {
        return {
            rank: 2,
            label: 'Starting',
            dot: 'bg-chart-4',
            text: 'text-chart-4',
            pulse: true,
        };
    }

    if (container.update_status === 'update_available') {
        return {
            rank: 3,
            label: 'Update',
            dot: 'bg-chart-4',
            text: 'text-chart-4',
            pulse: false,
        };
    }

    return {
        rank: 4,
        label: uptime(container.status),
        dot: 'bg-chart-3',
        text: 'text-chart-3',
        pulse: true,
    };
};

/** "Up 3 days (healthy)" → "3d". */
const uptime = (status: string | null): string => {
    const match = (status ?? '').match(
        /^Up (?:about )?(an?|\d+) (second|minute|hour|day|week|month|year)/i,
    );

    if (!match) {
        return 'Running';
    }

    const amount = /^an?$/i.test(match[1]) ? '1' : match[1];
    const units: Record<string, string> = {
        second: 's',
        minute: 'm',
        hour: 'h',
        day: 'd',
        week: 'w',
        month: 'mo',
        year: 'y',
    };

    return `${amount}${units[match[2].toLowerCase()]}`;
};

const rows = computed(() =>
    props.containers
        .map((container) => ({ container, health: health(container) }))
        .sort(
            (a, b) =>
                a.health.rank - b.health.rank ||
                (a.container.display_name ?? a.container.name).localeCompare(
                    b.container.display_name ?? b.container.name,
                ),
        ),
);

const summary = computed(() => [
    {
        label: 'Total',
        value: props.containers.length,
        valueClass: 'text-foreground',
    },
    {
        label: 'Running',
        value: props.containers.filter((c) => c.state === 'running').length,
        valueClass: 'text-chart-3',
    },
    {
        label: 'Issues',
        value: rows.value.filter((row) => row.health.rank <= 1).length,
        valueClass: 'text-destructive',
    },
    {
        label: 'Updates',
        value: props.containers.filter(
            (c) => c.update_status === 'update_available',
        ).length,
        valueClass: 'text-chart-4',
    },
]);
</script>

<template>
    <WidgetShell :mode="mode">
        <div
            class="rounded-xl border bg-card/70 p-3.5 text-card-foreground shadow-xl backdrop-blur-xl"
        >
            <SectionLabel icon="box" text="Container Status" />

            <div
                v-if="!containers.length"
                class="flex flex-col items-center gap-1.5 py-4 text-center"
            >
                <Icon
                    icon="simple-icons:portainer"
                    class="h-5 w-5 text-muted-foreground/50"
                />
                <p class="text-xs text-muted-foreground">
                    Add a Portainer instance and sync it to see your containers.
                </p>
                <a
                    href="/admin/portainers"
                    class="text-xs font-medium text-primary hover:underline"
                    >Manage Portainer</a
                >
            </div>

            <div
                v-else
                class="widget-md:grid widget-md:grid-cols-[1fr_auto] widget-md:gap-4"
            >
                <!-- Needs-attention first; the list scrolls in a side zone -->
                <div
                    class="max-h-72 space-y-1.5 overflow-y-auto pr-1 widget-md:grid widget-md:max-h-none widget-md:grid-cols-[repeat(auto-fill,minmax(11rem,1fr))] widget-md:gap-2 widget-md:space-y-0 widget-md:overflow-visible widget-md:pr-0"
                >
                    <component
                        :is="container.url ? 'a' : 'div'"
                        v-for="{ container, health: state } in rows"
                        :key="container.id"
                        :href="container.url ?? undefined"
                        :target="container.url ? '_blank' : undefined"
                        rel="noreferrer"
                        class="flex items-center justify-between gap-2 text-[12px] widget-md:rounded-lg widget-md:border widget-md:border-border/40 widget-md:bg-background/40 widget-md:px-2.5 widget-md:py-2"
                        :class="{ 'hover:text-primary': container.url }"
                        :title="container.status ?? container.state"
                    >
                        <span
                            class="flex min-w-0 items-center gap-1.5 text-foreground/85"
                        >
                            <span class="relative flex h-1.5 w-1.5 shrink-0">
                                <span
                                    v-if="state.pulse"
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75"
                                    :class="state.dot"
                                ></span>
                                <span
                                    class="relative inline-flex h-1.5 w-1.5 rounded-full"
                                    :class="state.dot"
                                ></span>
                            </span>
                            <img
                                v-if="container.icon"
                                :src="container.icon"
                                alt=""
                                class="h-3.5 w-3.5 shrink-0 object-contain"
                            />
                            <span class="truncate">{{
                                container.display_name || container.name
                            }}</span>
                        </span>
                        <span class="shrink-0 font-mono" :class="state.text">{{
                            state.label
                        }}</span>
                    </component>
                </div>

                <div
                    class="mt-2 border-t border-border/10 pt-2 text-center widget-md:hidden"
                >
                    <span class="font-mono text-[11px] text-muted-foreground">
                        {{ summary[0].value }} total ·
                        {{ summary[1].value }} running<template
                            v-if="summary[2].value"
                        >
                            ·
                            <span class="text-destructive"
                                >{{ summary[2].value }} issues</span
                            ></template
                        >
                        <template v-if="summary[3].value">
                            ·
                            <span class="text-chart-4"
                                >{{ summary[3].value }} updates</span
                            ></template
                        >
                    </span>
                </div>

                <div class="hidden gap-2 widget-md:flex widget-md:flex-col">
                    <div
                        v-for="item in summary"
                        :key="item.label"
                        class="flex min-w-28 items-baseline justify-between gap-3 rounded-lg border border-border/40 bg-background/40 px-3 py-1.5"
                    >
                        <span
                            class="text-[11px] tracking-wider text-muted-foreground uppercase"
                            >{{ item.label }}</span
                        >
                        <span
                            class="font-mono text-lg font-bold"
                            :class="item.valueClass"
                            >{{ item.value }}</span
                        >
                    </div>
                </div>
            </div>
        </div>
    </WidgetShell>
</template>
