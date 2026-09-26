<script setup lang="ts">
import { usePoll } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Skeleton } from '@/components/ui/skeleton';
import { TriangleAlert } from 'lucide-vue-next';
import { useElementSize } from '@vueuse/core';

const props = defineProps<{
    serviceItem: any;
    initialData?: any;
    cachedData?: any;
}>();

// Poll Bazarr data every 5 minutes
usePoll(300000, { only: ['bazarr'] });

const bazarr = computed(() => {
    return { ...props.serviceItem, ...(props.initialData || props.cachedData || {}) };
});

const isLoading = computed(() => !props.initialData && !props.cachedData);

const cardRef = ref<HTMLElement | null>(null);
const { width } = useElementSize(cardRef);
const isCompact = computed(() => width.value > 0 && width.value < 370);
</script>

<template>
    <div v-if="isLoading && !bazarr.status" class="flex flex-col gap-3 w-full overflow-hidden" style="padding: var(--card-padding);">
        <div class="flex justify-between items-center mb-1">
            <div class="flex items-center gap-2">
                <Skeleton class="h-4 w-4 rounded" />
                <Skeleton class="h-4 w-12" />
            </div>
            <div class="flex gap-2">
                <Skeleton class="h-4 w-16 rounded-full hidden sm:block" />
                <Skeleton class="h-4 w-14 rounded-full" />
            </div>
        </div>
        <div class="flex justify-between rounded bg-muted/30 p-2 mt-1">
            <div v-for="i in 3" :key="i" class="text-center flex-1 flex flex-col items-center gap-1.5">
                <Skeleton class="h-3.5 w-10" />
                <Skeleton class="h-2.5 w-12" />
            </div>
        </div>
    </div>
    <template v-else>
        <!-- Bazarr Block -->
        <div ref="cardRef" class="flex flex-col" style="padding: var(--card-padding);">
            <!-- Header -->
            <div class="flex justify-between items-center" style="margin-bottom: var(--header-margin-bottom);">
                <a :href="bazarr.url" target="_blank" class="flex items-center gap-2.5 group cursor-pointer w-full overflow-hidden">
                    <img :src="bazarr.icon" class="rounded-md shadow-sm group-hover:scale-105 transition-transform shrink-0" style="width: var(--header-icon-size); height: var(--header-icon-size);" alt="" />
                    <div class="flex flex-col overflow-hidden">
                        <h2 class="text-sm font-bold text-foreground group-hover:text-primary transition-colors truncate">Bazarr</h2>
                        <span class="text-muted-foreground/80 -mt-0.5 truncate" style="font-size: var(--header-url-size);">{{ bazarr.url?.replace(/^https?:\/\//, '') }}</span>
                    </div>
                </a>
                <div class="flex flex-col items-end gap-1.5 shrink-0 ml-2">
                    <div class="flex items-center gap-1.5 border border-amber-500/30 bg-amber-500/10 text-amber-500 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                        :class="{ '!border-destructive/30 !bg-destructive/10 !text-destructive': bazarr.status === 'Warning' }">
                        <div class="w-1.5 h-1.5 rounded-full bg-amber-500"
                            :class="{ '!bg-destructive': bazarr.status === 'Warning' }"></div>
                        {{ bazarr.status }}
                    </div>
                </div>
            </div>

            <!-- Issues Row -->
            <div v-if="bazarr.warnings > 0" class="flex flex-wrap gap-2 mb-4">
                <div class="flex items-center gap-1.5 text-destructive bg-destructive/10 border border-destructive/20 px-2.5 py-1 rounded-full text-[10px] font-bold">
                    <TriangleAlert class="w-3 h-3" /> {{ bazarr.warnings }} Issues
                </div>
            </div>

            <!-- Stats Grid -->
            <div :class="isCompact ? 'flex flex-col' : 'grid grid-cols-3'" style="gap: var(--stat-gap);">
                <div v-for="stat in bazarr.stats" :key="stat.label" 
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
