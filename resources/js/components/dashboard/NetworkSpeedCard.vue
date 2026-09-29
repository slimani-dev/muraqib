<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { router } from '@inertiajs/vue3';
import { computed, inject, ref } from 'vue';
import { Skeleton } from '../ui/skeleton';
import Sparkline from '../ui/Sparkline.vue';
import { dashboardContextKey } from './dashboardContext';
import SectionLabel from './SectionLabel.vue';
import type { WidgetMode } from './widgets/widgetMode';
import WidgetShell from './widgets/WidgetShell.vue';

const props = withDefaults(
    defineProps<{
        /**
         * A Netdata server from the dashboard's live Netdata data; its first network interface is shown.
         * Undefined while loading, null when there is no Netdata server.
         */
        server?: any;
        /** Server-side ping to google.com, used when Netdata has no ping chart. */
        latency?: { ms: number; host: string; measured_at: string } | null;
        mode?: WidgetMode;
    }>(),
    {
        server: undefined,
        latency: undefined,
        mode: 'desktop',
    },
);

const net = computed(() => props.server?.stats?.networks?.[0] ?? null);
const netdataPing = computed(() => props.server?.stats?.ping ?? null);

/** Shared scale so the download and upload lines can be compared. */
const chartMax = computed(() =>
    Math.max(1, ...(net.value?.rx_chart ?? []), ...(net.value?.tx_chart ?? [])),
);

const ping = computed(() => {
    if (netdataPing.value) {
        return {
            ms: netdataPing.value.ms,
            title: `Netdata ping to ${netdataPing.value.host}`,
        };
    }

    if (props.latency) {
        return {
            ms: props.latency.ms,
            title: `Ping to ${props.latency.host} from the server, measured ${new Date(props.latency.measured_at).toLocaleTimeString()}`,
        };
    }

    return null;
});

const pingColor = computed(() => {
    const ms = ping.value?.ms ?? 0;

    return ms < 50
        ? 'text-chart-3'
        : ms < 120
          ? 'text-chart-4'
          : 'text-destructive';
});

const emit = defineEmits<{
    /** Ask the dashboard to fetch Netdata again (it owns the Netdata polling). */
    refresh: [];
}>();

const dashboard = inject(dashboardContextKey, null);

const refreshing = ref(false);

/**
 * Refresh everything the widget shows: Netdata traffic (through the dashboard) and a new
 * server-side ping. Useful after a lost connection. The ping is otherwise measured at most twice an hour.
 */
const refresh = (): void => {
    refreshing.value = true;
    emit('refresh');
    dashboard?.refreshNetdata();
    router.reload({
        only: ['network_latency'],
        headers: { 'X-Latency-Refresh': '1' },
        onFinish: () => {
            refreshing.value = false;
        },
    });
};

const formatRate = (bytesPerSecond: number): string => {
    const units = ['B/s', 'KB/s', 'MB/s', 'GB/s'];
    let value = bytesPerSecond;
    let unit = 0;

    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit++;
    }

    return `${value.toFixed(value < 10 && unit > 0 ? 1 : 0)} ${units[unit]}`;
};
</script>

<template>
    <WidgetShell :mode="mode">
        <div
            class="flex h-full flex-col gap-2.5 rounded-xl border bg-card/70 p-3.5 text-card-foreground shadow-xl backdrop-blur-xl transition-all duration-200 hover:border-primary/50"
        >
            <div class="flex items-center justify-between gap-2">
                <SectionLabel icon="activity" text="Network" />
                <div class="-mt-2 flex min-w-0 items-center gap-1.5">
                    <span
                        v-if="net"
                        class="truncate font-mono text-[10px] text-muted-foreground"
                        :title="`${server.name} · ${net.name}`"
                    >
                        {{ server.name }} · {{ net.name }}
                    </span>
                    <button
                        type="button"
                        class="flex h-5 w-5 shrink-0 cursor-pointer items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground disabled:cursor-default disabled:opacity-50"
                        :disabled="refreshing"
                        title="Refresh network data and ping"
                        @click="refresh"
                    >
                        <Icon
                            icon="lucide:refresh-cw"
                            class="h-3 w-3"
                            :class="{ 'animate-spin': refreshing }"
                        />
                    </button>
                </div>
            </div>

            <template v-if="net">
                <div class="grid grid-cols-3 gap-2 font-mono text-[12px]">
                    <div class="flex flex-col">
                        <span
                            class="flex items-center gap-1 text-[10px] text-primary"
                            ><Icon
                                icon="lucide:arrow-down"
                                class="h-2.5 w-2.5"
                            />
                            Down</span
                        >
                        <span class="truncate font-bold widget-md:text-xl">{{
                            net.rx_formatted
                        }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span
                            class="flex items-center gap-1 text-[10px] text-chart-1"
                            ><Icon icon="lucide:arrow-up" class="h-2.5 w-2.5" />
                            Up</span
                        >
                        <span class="truncate font-bold widget-md:text-xl">{{
                            net.tx_formatted
                        }}</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] text-muted-foreground"
                            >Ping</span
                        >
                        <span
                            v-if="ping"
                            class="font-bold widget-md:text-xl"
                            :class="pingColor"
                            :title="ping.title"
                            >{{ ping.ms }} ms</span
                        >
                        <Skeleton
                            v-else-if="latency === undefined"
                            class="mt-1 h-3.5 w-12"
                        />
                        <span
                            v-else
                            class="font-bold text-muted-foreground"
                            title="Couldn't reach google.com"
                            >--</span
                        >
                    </div>
                </div>

                <!-- Download and upload on one shared scale -->
                <div class="relative h-14 w-full widget-md:h-20 widget-lg:h-24">
                    <div class="absolute inset-0">
                        <Sparkline
                            :data="net.rx_chart ?? []"
                            :min="0"
                            :max="chartMax"
                            color="var(--primary)"
                            :stroke-width="1.2"
                            :formatter="(v: number) => `↓ ${formatRate(v)}`"
                        />
                    </div>
                    <div class="pointer-events-none absolute inset-0">
                        <Sparkline
                            :data="net.tx_chart ?? []"
                            :min="0"
                            :max="chartMax"
                            color="var(--chart-2)"
                            :stroke-width="1.2"
                        />
                    </div>
                </div>
            </template>

            <div
                v-else-if="server === undefined || server?.stats?.is_partial"
                class="space-y-2"
            >
                <Skeleton class="h-8 w-full" />
                <Skeleton class="h-14 w-full" />
            </div>

            <div
                v-else
                class="flex flex-1 flex-col items-center justify-center gap-1 py-4 text-center"
            >
                <Icon
                    icon="lucide:activity"
                    class="h-6 w-6 text-muted-foreground/50"
                />
                <p class="text-xs text-muted-foreground">
                    Add a Netdata server in the admin panel to see network
                    traffic.
                </p>
            </div>
        </div>
    </WidgetShell>
</template>
