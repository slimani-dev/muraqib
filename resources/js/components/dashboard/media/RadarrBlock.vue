<script setup lang="ts">
import { useElementSize } from '@vueuse/core';
import { TriangleAlert } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { Skeleton } from '@/components/ui/skeleton';
import MediaStatusPill from './MediaStatusPill.vue';

const props = defineProps<{
    serviceItem: any;
    initialData?: any;
    cachedData?: any;
}>();

const radarr = computed(() => {
    return { ...props.serviceItem, ...(props.initialData || props.cachedData || {}) };
});

const isLoading = computed(() => !props.initialData && !props.cachedData);

const cardRef = ref<HTMLElement | null>(null);
const { width } = useElementSize(cardRef);
const isCompact = computed(() => width.value > 0 && width.value < 370);
</script>

<template>
    <div v-if="isLoading && !radarr.status" class="flex flex-col gap-3 w-full overflow-hidden" style="padding: var(--card-padding);">
        <div class="flex justify-between items-center mb-1">
            <div class="flex items-center gap-2">
                <Skeleton class="h-4 w-4 rounded" />
                <Skeleton class="h-4 w-12" />
            </div>
            <div class="flex gap-2">
                <Skeleton class="h-4 w-16 rounded-full hidden @sm/widget:block" />
                <Skeleton class="h-4 w-14 rounded-full" />
            </div>
        </div>
        <div class="flex gap-3 mt-1 bg-muted/20 p-2 rounded justify-center">
            <Skeleton class="h-3 w-16 rounded-full" />
            <Skeleton class="h-3 w-16 rounded-full" />
        </div>
        <div class="flex justify-between rounded bg-muted/30 p-2 mt-1">
            <div v-for="i in 3" :key="i" class="text-center flex-1 flex flex-col items-center gap-1.5">
                <Skeleton class="h-3.5 w-10" />
                <Skeleton class="h-2.5 w-12" />
            </div>
        </div>
    </div>
    <template v-else>
        <!-- Radarr Block -->
        <div ref="cardRef" class="flex flex-col" style="padding: var(--card-padding);">
            <!-- Header -->
            <div class="flex justify-between items-center" style="margin-bottom: var(--header-margin-bottom);">
                <a :href="radarr.url" target="_blank" class="flex items-center gap-2.5 group cursor-pointer w-full overflow-hidden">
                    <img :src="radarr.icon" class="rounded-md shadow-sm group-hover:scale-105 transition-transform shrink-0" style="width: var(--header-icon-size); height: var(--header-icon-size);" alt="" />
                    <div class="flex flex-col overflow-hidden">
                        <h2 class="text-sm font-bold text-foreground group-hover:text-primary transition-colors truncate">{{ radarr.name ?? 'Radarr' }}</h2>
                        <span class="text-muted-foreground/80 -mt-0.5 truncate" style="font-size: var(--header-url-size);">{{ radarr.url?.replace(/^https?:\/\//, '') }}</span>
                    </div>
                </a>
                <div class="flex flex-col items-end gap-1.5 shrink-0 ml-2">
                    <MediaStatusPill :warning="radarr.status === 'Warning'" :fallback="radarr.status" />
                </div>
            </div>

            <!-- Queue Pills -->
            <div v-if="radarr.warnings > 0 || radarr.queue?.downloading > 0 || radarr.queue?.failed > 0 || radarr.queue?.importing > 0"
                class="flex flex-wrap gap-2 mb-4">
                <div v-if="radarr.warnings > 0"
                    class="flex items-center gap-1.5 text-destructive bg-destructive/10 border border-destructive/20 px-2.5 py-1 rounded-full text-[10px] font-bold">
                    <TriangleAlert class="w-3 h-3" /> {{ radarr.warnings }} Issues
                </div>
                <div v-if="radarr.queue.downloading > 0" class="flex items-center gap-1.5 text-primary bg-primary/10 border border-primary/20 px-2.5 py-1 rounded-full text-[10px] font-bold">
                    <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
                    {{ radarr.queue.downloading }} Downloading
                </div>
                <div v-if="radarr.queue.importing > 0" class="flex items-center gap-1.5 text-chart-4 bg-chart-4/10 border border-chart-4/20 px-2.5 py-1 rounded-full text-[10px] font-bold">
                    <span class="w-1.5 h-1.5 rounded-full bg-chart-4 animate-pulse"></span>
                    {{ radarr.queue.importing }} Importing
                </div>
                <div v-if="radarr.queue.failed > 0" class="flex items-center gap-1.5 text-destructive bg-destructive/10 border border-destructive/20 px-2.5 py-1 rounded-full text-[10px] font-bold">
                    <span class="w-1.5 h-1.5 rounded-full bg-destructive"></span>
                    {{ radarr.queue.failed }} Failed
                </div>
            </div>

            <!-- Stats Grid -->
            <div :class="isCompact ? 'flex flex-col' : 'grid grid-cols-3'" style="gap: var(--stat-gap);">
                <div v-for="stat in radarr.stats" :key="stat.label" 
                    :class="['bg-background/60 border border-border/50 shadow-sm hover:bg-background/80 hover:border-border/60 transition-all', 
                    isCompact ? 'flex flex-row items-center justify-between' : 'flex flex-col items-center justify-center']" 
                    style="border-radius: var(--stat-radius); padding: var(--stat-padding);">
                    
                    <template v-if="isCompact">
                        <div class="text-foreground/80 uppercase tracking-wider font-bold" style="font-size: var(--stat-label-size);">{{ stat.label }}</div>
                        <div class="font-mono font-black tracking-tight drop-shadow-sm" :class="stat.color" style="font-size: var(--stat-value-size);">{{ stat.value }}</div>
                    </template>
                    <template v-else>
                        <div class="font-mono font-black tracking-tight drop-shadow-sm" :class="stat.color" style="font-size: var(--stat-value-size);">{{ stat.value }}</div>
                        <div class="text-foreground/80 mt-1 uppercase tracking-wider font-bold" style="font-size: var(--stat-label-size);">{{ stat.label }}</div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</template>
