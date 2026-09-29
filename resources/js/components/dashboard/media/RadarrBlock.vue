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
        v-if="isLoading && !radarr.status"
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
        <div class="mt-1 flex justify-center gap-3 rounded bg-muted/20 p-2">
            <Skeleton class="h-3 w-16 rounded-full" />
            <Skeleton class="h-3 w-16 rounded-full" />
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
        <!-- Radarr Block -->
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
                    :href="radarr.url"
                    target="_blank"
                    class="group flex w-full cursor-pointer items-center gap-2.5 overflow-hidden"
                >
                    <img
                        :src="radarr.icon"
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
                            {{ radarr.name ?? 'Radarr' }}
                        </h2>
                        <span
                            class="-mt-0.5 truncate text-muted-foreground/80"
                            style="font-size: var(--header-url-size)"
                            >{{ radarr.url?.replace(/^https?:\/\//, '') }}</span
                        >
                    </div>
                </a>
                <div class="ml-2 flex shrink-0 flex-col items-end gap-1.5">
                    <MediaStatusPill
                        :warning="radarr.status === 'Warning'"
                        :fallback="radarr.status"
                    />
                </div>
            </div>

            <!-- Queue Pills -->
            <div
                v-if="
                    radarr.warnings > 0 ||
                    radarr.queue?.downloading > 0 ||
                    radarr.queue?.failed > 0 ||
                    radarr.queue?.importing > 0
                "
                class="mb-4 flex flex-wrap gap-2"
            >
                <div
                    v-if="radarr.warnings > 0"
                    class="flex items-center gap-1.5 rounded-full border border-destructive/20 bg-destructive/10 px-2.5 py-1 text-[10px] font-bold text-destructive"
                >
                    <TriangleAlert class="h-3 w-3" />
                    {{ radarr.warnings }} Issues
                </div>
                <div
                    v-if="radarr.queue.downloading > 0"
                    class="flex items-center gap-1.5 rounded-full border border-primary/20 bg-primary/10 px-2.5 py-1 text-[10px] font-bold text-primary"
                >
                    <span
                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-primary"
                    ></span>
                    {{ radarr.queue.downloading }} Downloading
                </div>
                <div
                    v-if="radarr.queue.importing > 0"
                    class="flex items-center gap-1.5 rounded-full border border-chart-4/20 bg-chart-4/10 px-2.5 py-1 text-[10px] font-bold text-chart-4"
                >
                    <span
                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-chart-4"
                    ></span>
                    {{ radarr.queue.importing }} Importing
                </div>
                <div
                    v-if="radarr.queue.failed > 0"
                    class="flex items-center gap-1.5 rounded-full border border-destructive/20 bg-destructive/10 px-2.5 py-1 text-[10px] font-bold text-destructive"
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full bg-destructive"
                    ></span>
                    {{ radarr.queue.failed }} Failed
                </div>
            </div>

            <!-- Stats Grid -->
            <div
                :class="isCompact ? 'flex flex-col' : 'grid grid-cols-3'"
                style="gap: var(--stat-gap)"
            >
                <div
                    v-for="stat in radarr.stats"
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
