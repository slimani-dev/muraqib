<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { computed, inject } from 'vue';
import { mediaWidgetKey } from './mediaWidget';

const props = defineProps<{
    /** The app reports problems (e.g. Radarr health warnings) while being accessible. */
    warning?: boolean;
    /** Shown when the app has no status check (no public URL). */
    fallback?: string;
    /** Dot only, for headers that already show another pill. */
    compact?: boolean;
}>();

const widget = inject(mediaWidgetKey, null);

const appearance = computed(() => {
    switch (widget?.status.value) {
        case 'up':
            return props.warning
                ? { label: 'Warning', classes: 'border-destructive/30 bg-destructive/10 text-destructive', dot: 'bg-destructive' }
                : { label: 'Online', classes: 'border-chart-3/30 bg-chart-3/10 text-chart-3', dot: 'bg-chart-3' };
        case 'blocked':
            return { label: 'Blocked', classes: 'border-chart-4/30 bg-chart-4/10 text-chart-4', dot: 'bg-chart-4' };
        case 'down':
            return { label: 'Offline', classes: 'border-destructive/30 bg-destructive/10 text-destructive', dot: 'bg-destructive' };
        case 'checking':
            return { label: 'Checking', classes: 'border-border bg-muted/40 text-muted-foreground', dot: 'bg-muted-foreground animate-pulse' };
        default:
            return { label: props.fallback ?? 'Unknown', classes: 'border-border bg-muted/40 text-muted-foreground', dot: 'bg-muted-foreground' };
    }
});

const title = computed(() => (widget?.status.value === 'blocked'
    ? 'Reachable, but the status check headers were rejected'
    : `Status: ${appearance.value.label}`));
</script>

<template>
    <div class="flex shrink-0 items-center gap-1.5">
        <div v-if="compact" class="h-2 w-2 rounded-full" :class="appearance.dot" :title="title"></div>
        <div v-else class="flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
            :class="appearance.classes" :title="title">
            <div class="h-1.5 w-1.5 rounded-full" :class="appearance.dot"></div>
            {{ appearance.label }}
        </div>
        <button v-if="widget" type="button" class="flex h-5 w-5 cursor-pointer items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground disabled:cursor-default disabled:opacity-50"
            :disabled="widget.refreshing.value" title="Refresh" @click.prevent.stop="widget.refresh()">
            <Icon icon="lucide:refresh-cw" class="h-3 w-3" :class="{ 'animate-spin': widget.refreshing.value }" />
        </button>
    </div>
</template>
