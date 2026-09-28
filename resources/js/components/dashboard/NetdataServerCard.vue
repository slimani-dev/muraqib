<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { computed, inject } from 'vue';
import { Progress } from '../ui/progress';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '../ui/select';
import { Skeleton } from '../ui/skeleton';
import Sparkline from '../ui/Sparkline.vue';
import { dashboardContextKey } from './dashboardContext';
import { tileGrid } from './widgets/tileRows';
import type { WidgetMode } from './widgets/widgetMode';
import WidgetShell from './widgets/WidgetShell.vue';

const props = withDefaults(defineProps<{
    server: any; // Raw server object with 'stats'
    loading?: boolean;
    timeframe?: string;
    mode?: WidgetMode;
}>(), {
    mode: 'desktop',
});

const emit = defineEmits(['update:timeframe']);

const dashboard = inject(dashboardContextKey, null);

const changeTimeframe = (timeframe: string): void => {
    emit('update:timeframe', timeframe);
    dashboard?.setNetdataTimeframe(props.server?.id, timeframe);
};

const stats = computed(() => props.server?.stats);

/** CPU + RAM + one tile per network interface (or a placeholder while it loads). */
const chartCount = computed(() => 2 + (stats.value?.networks?.length || (stats.value?.is_partial ? 1 : 0)));

const chartGrid = (layout: WidgetMode) => tileGrid(chartCount.value, layout);

/** Disks: same rows as the charts at 2 and 3 columns, stacked one per row at 1 column. */
const diskGrid = (layout: WidgetMode) => tileGrid(stats.value?.disks?.length ?? 0, layout, 1);

const getStatusColor = (status: string) => {
    switch (status?.toLowerCase()) {
        case 'active':
        case 'ok':
        case 'online':
            return 'border-chart-3/20 bg-chart-3/10 text-chart-3';
        case 'offline':
        case 'error':
            return 'border-destructive/20 bg-destructive/10 text-destructive animate-pulse';
        default:
            return 'border-border/50 bg-muted text-muted-foreground';
    }
};

const getCpuColor = (usage: number) => {
    if (usage > 80) {
return 'text-destructive';
}

    if (usage > 50) {
return 'text-warning';
} // if warning color exists, else text-amber-500

    return 'text-chart-3';
};

const getCpuChartColor = (usage: number) => {
    if (usage > 80) {
return '#ef4444';
} // destructive

    if (usage > 50) {
return '#f59e0b';
} // warning

    return '#22c55e'; // success / chart-3
};

const formatSize = (bytes: number) => {
    if (bytes === 0) {
return '0 B';
}

    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

const getOsIcon = (osId: string) => {
    if (!osId) {
return 'lucide:server';
}

    const id = osId.toLowerCase();

    if (id.includes('ubuntu')) {
return 'simple-icons:ubuntu';
}

    if (id.includes('debian')) {
return 'simple-icons:debian';
}

    if (id.includes('alpine')) {
return 'simple-icons:alpinelinux';
}

    if (id.includes('centos')) {
return 'simple-icons:centos';
}

    if (id.includes('fedora')) {
return 'simple-icons:fedora';
}

    if (id.includes('arch')) {
return 'simple-icons:archlinux';
}

    if (id.includes('suse')) {
return 'simple-icons:opensuse';
}

    if (id.includes('redhat') || id.includes('rhel')) {
return 'simple-icons:redhat';
}

    if (id.includes('proxmox')) {
return 'simple-icons:proxmox';
}

    if (id.includes('linux')) {
return 'simple-icons:linux';
}

    if (id.includes('windows')) {
return 'simple-icons:windows';
}

    if (id.includes('mac') || id.includes('darwin')) {
return 'simple-icons:apple';
}

    return 'lucide:server';
};
</script>

<template>
    <WidgetShell v-slot="{ layout }" :mode="mode" class="mb-4">
    <div class="rounded-2xl border bg-card shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden flex flex-col"
        :class="{
            'border-destructive shadow-sm shadow-destructive/20': server?.status === 'offline'
        }" style="padding: var(--card-padding); gap: var(--card-gap);">
        <!-- Skeleton Loading State -->
        <template v-if="loading && !stats">
            <div class="flex items-center justify-between" style="margin-bottom: var(--header-margin-bottom);">
                <div class="flex items-center" style="gap: var(--header-gap);">
                    <Skeleton class="h-6 w-24 rounded-md" />
                    <Skeleton class="h-5 w-16 rounded-md" />
                </div>
                <Skeleton class="h-5 w-12 rounded-full" />
            </div>
            <div class="space-y-3">
                <Skeleton class="h-4 w-full" />
                <Skeleton class="h-4 w-full" />
                <Skeleton class="h-4 w-full" />
            </div>
        </template>

        <!-- Actual Content -->
        <template v-else>
            <!-- Header -->
            <div class="flex justify-between items-center">
                <div class="flex items-center" style="gap: var(--header-gap);">
                    <div class="flex items-center justify-center rounded-md shadow-sm border bg-muted/50 text-primary"
                        style="width: var(--header-icon-size); height: var(--header-icon-size);">
                        <Icon :icon="getOsIcon(stats?.info?.os_id || stats?.info?.os || server?.type)"
                            class="h-5 w-5" />
                    </div>
                    <div class="flex flex-col justify-center">
                        <h2 class="font-bold text-foreground transition-colors group-hover:text-primary leading-tight mb-0.5"
                            style="font-size: var(--header-title-size);">{{ stats?.info?.hostname || server.name }}</h2>
                        <span v-if="stats?.info?.ip" class="font-mono text-muted-foreground leading-tight mb-1"
                            style="font-size: var(--header-url-size);">
                            {{ stats.info.ip }}
                        </span>
                        <span v-if="stats?.info?.os"
                            class="text-muted-foreground/70 leading-none truncate max-w-[200px]"
                            style="font-size: 10px;">
                            {{ stats.info.kernel_name || 'Linux' }} <span class="opacity-50 mx-0.5">-</span> {{
                                stats.info.os }}
                        </span>
                    </div>
                </div>

                <div class="flex flex-col items-end gap-1.5">
                    <span :class="getStatusColor(!stats ? 'offline' : (stats.status || server.status))"
                        class="flex items-center font-bold shrink-0 uppercase tracking-wider"
                        style="gap: var(--stat-gap); padding: var(--badge-py) var(--badge-px); font-size: var(--badge-font-size); border-radius: var(--badge-radius);">
                        <span v-if="stats && (stats.status || server.status)?.toLowerCase() === 'online'"
                            class="relative flex" style="width: var(--badge-dot-size); height: var(--badge-dot-size);">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-current opacity-75"></span>
                            <span class="relative inline-flex h-full w-full rounded-full bg-current"></span>
                        </span>
                        <Icon v-else icon="lucide:alert-circle" class="h-3 w-3" />
                        {{ !stats ? 'OFFLINE' : (stats.status || server.status)?.toUpperCase() }}
                    </span>

                    <Select v-if="stats" :model-value="timeframe || '1h'"
                        @update:model-value="changeTimeframe($event as string)">
                        <SelectTrigger
                            class="!h-5 w-[75px] text-[10px] bg-background/50 border-border/50 !py-0 !px-1.5 font-mono hover:bg-background/80 transition-colors">
                            <SelectValue placeholder="Time" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem value="1h" class="text-[11px] font-mono">1 Hour</SelectItem>
                                <SelectItem value="6h" class="text-[11px] font-mono">6 Hours</SelectItem>
                                <SelectItem value="12h" class="text-[11px] font-mono">12 Hours</SelectItem>
                                <SelectItem value="24h" class="text-[11px] font-mono">24 Hours</SelectItem>
                                <SelectItem value="3d" class="text-[11px] font-mono">3 Days</SelectItem>
                                <SelectItem value="7d" class="text-[11px] font-mono">7 Days</SelectItem>
                                <SelectItem value="1m" class="text-[11px] font-mono">1 Month</SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <div v-if="stats" :style="{ ...chartGrid(layout).grid, gap: 'var(--stat-gap)' }">
                    <!-- Chart tiles: CPU, RAM, one per network interface. Rows come from tileRows(). -->
                    <!-- CPU -->
                    <div class="bg-background/60 border border-border/50 flex flex-col shadow-sm hover:bg-background/80 hover:border-border/60 transition-all relative overflow-hidden group min-h-[95px] widget-md:min-h-[120px]"
                        
                        :style="chartGrid(layout).tile(0)" style="padding: var(--stat-padding); border-radius: var(--stat-radius);">
                        <div class="relative z-10 flex flex-col gap-1.5 w-full">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5">
                                    <Icon icon="lucide:cpu" class="h-4 w-4 text-muted-foreground" />
                                    <span class="font-mono font-semibold uppercase tracking-wider text-muted-foreground"
                                        style="font-size: 13px;">CPU</span>
                                </div>
                                <span class="font-mono font-bold" :class="getCpuColor(stats.cpu?.usage || 0)"
                                    style="font-size: 14px;">
                                    {{ stats.cpu?.usage !== undefined ? stats.cpu?.usage + '%' : '--%' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between w-full font-mono text-white/80"
                                style="font-size: 10px;">
                                <div class="truncate mr-2" :title="stats.cpu?.model">{{ stats.cpu?.model }}</div>
                                <div class="shrink-0">{{ stats.cpu?.cores }} Cores</div>
                            </div>
                        </div>

                        <!-- Background Chart -->
                        <div
                            class="absolute inset-x-0 bottom-0 h-1/2 opacity-60 group-hover:opacity-80 transition-opacity z-0 pointer-events-auto">
                            <Sparkline v-if="stats.cpu?.chart" :data="stats.cpu?.chart" :color="getCpuChartColor(stats.cpu?.usage || 0)"
                                :stroke-width="1.0" :formatter="(v) => v.toFixed(1) + '%'" />
                        </div>
                    </div>

                    <!-- RAM -->
                    <div class="bg-background/60 border border-border/50 flex flex-col shadow-sm hover:bg-background/80 hover:border-border/60 transition-all relative overflow-hidden group min-h-[95px] widget-md:min-h-[120px]"
                        
                        :style="chartGrid(layout).tile(1)" style="padding: var(--stat-padding); border-radius: var(--stat-radius);">
                        <div class="relative z-10 flex flex-col gap-1.5 w-full">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-1.5">
                                    <Icon icon="lucide:memory-stick" class="h-4 w-4 text-muted-foreground" />
                                    <span class="font-mono font-semibold uppercase tracking-wider text-muted-foreground"
                                        style="font-size: 13px;">RAM</span>
                                </div>
                                <span class="font-mono font-bold" :class="getCpuColor(stats.memory?.percent || 0)"
                                    style="font-size: 14px;">
                                    {{ stats.memory?.percent !== undefined ? stats.memory?.percent + '%' : '--%' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between w-full font-mono text-white/80"
                                style="font-size: 10px;">
                                <div class="truncate">{{ stats.memory?.used_bytes !== undefined ? (stats.memory?.used_bytes / 1073741824).toFixed(1) : '--' }} / {{
                                    (stats.memory?.total_bytes / 1073741824).toFixed(1) }} GB</div>
                            </div>
                        </div>

                        <!-- Background Chart -->
                        <div
                            class="absolute inset-x-0 bottom-0 h-1/2 opacity-60 group-hover:opacity-80 transition-opacity z-0 pointer-events-auto">
                            <Sparkline v-if="stats.memory?.chart" :data="stats.memory?.chart" :color="getCpuChartColor(stats.memory?.percent || 0)"
                                :stroke-width="1.0" :formatter="(v) => (v / 1024).toFixed(1) + ' GB'" />
                        </div>
                    </div>

                <!-- Network -->
                    <template v-if="stats.networks && stats.networks.length > 0">
                        <div v-for="(net, netIndex) in stats.networks" :key="net.name"
                            class="bg-background/60 border border-border/50 flex flex-col shadow-sm hover:bg-background/80 hover:border-border/60 transition-all relative overflow-hidden group min-h-[95px] widget-md:min-h-[120px]"
                            :style="chartGrid(layout).tile(2 + (netIndex as number))" style="padding: var(--stat-padding); border-radius: var(--stat-radius);">
                            <div class="relative z-10 flex flex-col gap-1.5 w-full">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <Icon icon="lucide:arrow-down-up" class="h-4 w-4 text-muted-foreground" />
                                        <span class="font-mono font-semibold uppercase tracking-wider text-muted-foreground"
                                            style="font-size: 13px;">NETWORK</span>
                                    </div>
                                    <span class="font-mono font-bold text-white/90 flex items-center gap-1"
                                        style="font-size: 11px;">
                                        <span class="text-white/50">↓</span>{{ net.rx_formatted }} <span
                                            class="text-white/50 ml-1">↑</span>{{ net.tx_formatted }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between w-full font-mono text-white/80"
                                    style="font-size: 10px;">
                                    <div class="truncate mr-2" :title="net.name">{{ net.name }}</div>
                                </div>
                            </div>

                            <!-- Background Chart -->
                            <div
                                class="absolute inset-x-0 bottom-0 h-1/2 opacity-60 group-hover:opacity-80 transition-opacity z-0 pointer-events-auto">
                                <Sparkline :data="net.chart" color="#8b5cf6" :stroke-width="1.0"
                                    :formatter="(v) => formatSize(v) + '/s'" />
                            </div>
                        </div>
                    </template>
                    <template v-else-if="stats.is_partial">
                        <div class="bg-background/60 border border-border/50 flex flex-col shadow-sm relative overflow-hidden group min-h-[95px] widget-md:min-h-[120px]"
                            :style="chartGrid(layout).tile(2)" style="padding: var(--stat-padding); border-radius: var(--stat-radius);">
                            <div class="relative z-10 flex flex-col gap-1.5 w-full">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <Icon icon="lucide:arrow-down-up" class="h-4 w-4 text-muted-foreground" />
                                        <span class="font-mono font-semibold uppercase tracking-wider text-muted-foreground"
                                            style="font-size: 13px;">NETWORK</span>
                                    </div>
                                    <span class="font-mono font-bold text-white/90 flex items-center gap-1"
                                        style="font-size: 11px;">
                                        <span class="text-white/50">↓</span>-- <span
                                            class="text-white/50 ml-1">↑</span>--
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>

                <!-- Disks -->
                <!-- Same row rules as the chart tiles (tileRows()) -->
                <div class="col-span-full border bg-background/60 border-border/50 shadow-sm hover:bg-background/80 hover:border-border/60 transition-all relative overflow-hidden group"
                    :style="diskGrid(layout).grid" style="padding: var(--stat-padding); border-radius: var(--stat-radius); gap: var(--stat-gap);">
                    <div v-for="(disk, diskIndex) in stats.disks" :key="disk.id" class="flex min-w-0 flex-col"
                        :style="diskGrid(layout).tile(diskIndex as number)" style="gap: calc(var(--stat-gap) / 2);">
                        <div class="flex items-center justify-between gap-2 text-[11px] relative z-10 w-full">
                            <span class="flex min-w-0 items-center font-mono font-semibold text-white/90"
                                style="gap: calc(var(--stat-gap) / 2);" :title="disk.name">
                                <Icon icon="lucide:hard-drive" class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
                                <span class="truncate">{{ disk.name }}</span>
                            </span>
                            <div class="font-mono flex shrink-0 items-center whitespace-nowrap" style="gap: var(--stat-gap);">
                                <span class="text-white/70" style="font-size: 10px;">{{ disk.used_formatted }} / {{
                                    disk.total_formatted }}</span>
                                <span class="font-bold" :class="getCpuColor(disk.percent)">{{ disk.percent }}%</span>
                            </div>
                        </div>
                        <Progress :model-value="disk.percent" class="h-1.5 relative z-10 w-full" :class="[
                            disk.percent > 80 ? '[&>div]:bg-destructive bg-destructive/20' :
                                disk.percent > 50 ? '[&>div]:bg-amber-500 bg-amber-500/20' :
                                    '[&>div]:bg-primary bg-primary/20'
                        ]" />
                    </div>
                </div>
            </div> <!-- Close Main wrapper -->
        </template>
    </div>
    </WidgetShell>
</template>
