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

const bazarr = computed(() => {
    return {
        ...props.serviceItem,
        ...(props.initialData || props.cachedData || {}),
    };
});

const isLoading = computed(() => !props.initialData && !props.cachedData);

const cardRef = ref<HTMLElement | null>(null);
const { width } = useElementSize(cardRef);
const isCompact = computed(() => width.value > 0 && width.value < 370);
</script>

<template>
    <div
        v-if="isLoading && !bazarr.status"
        class="flex w-full flex-col gap-3 overflow-hidden"
        style="padding: var(--card-padding)"
    >
        <div class="mb-1 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <Skeleton class="h-4 w-4 rounded" />
                <Skeleton class="h-4 w-12" />
            </div>
            <div class="flex gap-2">
                <Skeleton
                    class="hidden h-4 w-16 rounded-full @sm/widget:block"
                />
                <Skeleton class="h-4 w-14 rounded-full" />
            </div>
        </div>
        <div class="mt-1 flex justify-between rounded bg-muted/30 p-2">
            <div
                v-for="i in 3"
                :key="i"
                class="flex flex-1 flex-col items-center gap-1.5 text-center"
            >
                <Skeleton class="h-3.5 w-10" />
                <Skeleton class="h-2.5 w-12" />
            </div>
        </div>
    </div>
    <template v-else>
        <!-- Bazarr Block -->
        <div
            ref="cardRef"
            class="flex flex-col"
            style="padding: var(--card-padding)"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between"
                style="margin-bottom: var(--header-margin-bottom)"
            >
                <a
                    :href="bazarr.url"
                    target="_blank"
                    class="group flex w-full cursor-pointer items-center gap-2.5 overflow-hidden"
                >
                    <img
                        :src="bazarr.icon"
                        class="shrink-0 rounded-md shadow-sm transition-transform group-hover:scale-105"
                        style="
                            width: var(--header-icon-size);
                            height: var(--header-icon-size);
                        "
                        alt=""
                    />
                    <div class="flex flex-col overflow-hidden">
                        <h2
                            class="truncate text-sm font-bold text-foreground transition-colors group-hover:text-primary"
                        >
                            {{ bazarr.name ?? 'Bazarr' }}
                        </h2>
                        <span
                            class="-mt-0.5 truncate text-muted-foreground/80"
                            style="font-size: var(--header-url-size)"
                            >{{ bazarr.url?.replace(/^https?:\/\//, '') }}</span
                        >
                    </div>
                </a>
                <div class="ml-2 flex shrink-0 flex-col items-end gap-1.5">
                    <MediaStatusPill
                        :warning="bazarr.status === 'Warning'"
                        :fallback="bazarr.status"
                    />
                </div>
            </div>

            <!-- Issues Row -->
            <div v-if="bazarr.warnings > 0" class="mb-4 flex flex-wrap gap-2">
                <div
                    class="flex items-center gap-1.5 rounded-full border border-destructive/20 bg-destructive/10 px-2.5 py-1 text-[10px] font-bold text-destructive"
                >
                    <TriangleAlert class="h-3 w-3" />
                    {{ bazarr.warnings }} Issues
                </div>
            </div>

            <!-- Stats Grid -->
            <div
                :class="isCompact ? 'flex flex-col' : 'grid grid-cols-3'"
                style="gap: var(--stat-gap)"
            >
                <div
                    v-for="stat in bazarr.stats"
                    :key="stat.label"
                    :class="[
                        'border border-border/50 bg-background/60 shadow-sm transition-all hover:border-border/60 hover:bg-background/80',
                        isCompact
                            ? 'flex flex-row items-center justify-between'
                            : 'flex flex-col items-center justify-center',
                    ]"
                    style="
                        border-radius: var(--stat-radius);
                        padding: var(--stat-padding);
                    "
                >
                    <template v-if="isCompact">
                        <div
                            class="font-bold tracking-wider text-foreground/80 uppercase"
                            style="font-size: var(--stat-label-size)"
                        >
                            {{ stat.label }}
                        </div>
                        <div
                            class="font-mono font-black tracking-tight drop-shadow-sm"
                            :class="stat.color"
                            style="font-size: var(--stat-value-size)"
                        >
                            {{ stat.value }}
                        </div>
                    </template>
                    <template v-else>
                        <div
                            class="font-mono font-black tracking-tight drop-shadow-sm"
                            :class="stat.color"
                            style="font-size: var(--stat-value-size)"
                        >
                            {{ stat.value }}
                        </div>
                        <div
                            class="mt-1 font-bold tracking-wider text-foreground/80 uppercase"
                            style="font-size: var(--stat-label-size)"
                        >
                            {{ stat.label }}
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </template>
</template>
