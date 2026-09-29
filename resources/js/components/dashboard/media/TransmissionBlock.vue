<script setup lang="ts">
import { computed } from 'vue';
import { Skeleton } from '@/components/ui/skeleton';
import MediaStatusPill from './MediaStatusPill.vue';

const props = defineProps<{
    serviceItem: any;
    initialData?: any;
    cachedData?: any;
}>();

const transmission = computed(() => {
    return {
        ...props.serviceItem,
        ...(props.initialData || props.cachedData || {}),
    };
});

const isLoading = computed(() => !props.initialData && !props.cachedData);
</script>

<template>
    <div
        v-if="isLoading && !transmission.status"
        class="flex w-full flex-col gap-3 overflow-hidden"
        style="padding: var(--card-padding)"
    >
        <div class="mb-1 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <Skeleton class="h-4 w-4 rounded" />
                <Skeleton class="h-4 w-20" />
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
                v-for="i in 4"
                :key="i"
                class="flex flex-1 flex-col items-center gap-1.5 text-center"
            >
                <Skeleton class="h-3.5 w-10" />
                <Skeleton class="h-2.5 w-12" />
            </div>
        </div>
    </div>
    <div
        v-else
        class="flex h-full flex-col overflow-hidden"
        style="padding: var(--card-padding)"
    >
        <!-- Header -->
        <div
            class="flex items-center justify-between"
            style="margin-bottom: var(--header-margin-bottom)"
        >
            <a
                :href="transmission.url"
                target="_blank"
                class="group flex cursor-pointer items-center gap-2.5"
            >
                <img
                    :src="transmission.icon"
                    class="rounded-md shadow-sm transition-transform group-hover:scale-105"
                    style="
                        width: var(--header-icon-size);
                        height: var(--header-icon-size);
                    "
                    alt=""
                />
                <div class="flex flex-col">
                    <h2
                        class="text-sm font-bold text-foreground transition-colors group-hover:text-primary"
                    >
                        {{ transmission.name ?? 'Transmission' }}
                    </h2>
                    <span
                        class="-mt-0.5 max-w-[200px] truncate text-muted-foreground/80"
                        style="font-size: var(--header-url-size)"
                        >{{
                            transmission.url?.replace(/^https?:\/\//, '')
                        }}</span
                    >
                </div>
            </a>
            <MediaStatusPill
                :warning="transmission.status === 'Warning'"
                :fallback="transmission.status"
            />
        </div>

        <!-- Global Speeds -->
        <div
            class="flex shrink-0"
            style="
                gap: var(--stat-gap);
                margin-bottom: var(--stat-margin-bottom);
            "
        >
            <div
                class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-chart-1/20 bg-chart-1/10 px-3 py-2 text-chart-1 shadow-sm"
            >
                <i data-lucide="arrow-down" class="h-4 w-4"></i>
                <span class="font-mono text-[13px] font-bold">{{
                    transmission.speeds?.down || '0 B/s'
                }}</span>
            </div>
            <div
                class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-chart-3/20 bg-chart-3/10 px-3 py-2 text-chart-3 shadow-sm"
            >
                <i data-lucide="arrow-up" class="h-4 w-4"></i>
                <span class="font-mono text-[13px] font-bold">{{
                    transmission.speeds?.up || '0 B/s'
                }}</span>
            </div>
        </div>

        <!-- Stats Grid -->
        <div
            class="grid shrink-0 grid-cols-4"
            style="
                gap: var(--stat-gap);
                margin-bottom: var(--stat-margin-bottom);
            "
        >
            <div
                v-for="stat in transmission.stats"
                :key="stat.label"
                class="flex flex-col items-center justify-center border border-border/50 bg-background/60 shadow-sm transition-all hover:border-border/60 hover:bg-background/80"
                style="
                    border-radius: var(--stat-radius);
                    padding: var(--stat-padding);
                "
            >
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
            </div>
        </div>

        <!-- Scrollable Torrents List -->
        <!-- Capped so a long torrent list scrolls instead of stretching the whole dashboard row -->
        <div
            class="-mr-2 max-h-[26rem] min-h-0 flex-1 space-y-3 overflow-y-auto pr-2"
        >
            <div
                v-if="!transmission.torrents?.length"
                class="flex h-full items-center justify-center text-xs font-semibold text-muted-foreground"
            >
                No active downloads
            </div>

            <div
                v-for="torrent in transmission.torrents"
                :key="torrent.id"
                class="group relative flex flex-col overflow-hidden border border-border/50 bg-background/60 shadow-sm transition-all hover:bg-background/80"
                style="
                    padding: var(--torrent-padding);
                    border-radius: var(--torrent-radius);
                "
            >
                <!-- Top row: Name -->
                <div
                    class="relative z-10 mb-1 line-clamp-1 truncate text-[12px] leading-tight font-bold text-foreground"
                    :title="torrent.name"
                >
                    {{ torrent.name }}
                </div>

                <!-- Middle row: Progress & Size info -->
                <div
                    class="relative z-10 text-[10px] font-medium text-muted-foreground/90"
                >
                    <template v-if="torrent.statusCode === 4">
                        {{ torrent.downloaded }} of {{ torrent.size }} ({{
                            torrent.progress
                        }}%)<span v-if="torrent.eta">
                            - {{ torrent.eta }} remaining</span
                        >
                    </template>
                    <template v-else>
                        {{ torrent.downloaded }} of {{ torrent.size }} ({{
                            torrent.progress
                        }}%), uploaded {{ torrent.uploaded }} (Ratio:
                        {{ torrent.ratio }})
                    </template>
                </div>

                <!-- Bottom row: Peers & Speed -->
                <div
                    class="relative z-10 mb-1.5 text-[10px] font-medium text-muted-foreground/90"
                >
                    <template v-if="torrent.statusCode === 4">
                        Downloading from {{ torrent.seeders }} of
                        {{ torrent.peers }} connected peers -
                        <span class="font-bold text-chart-1"
                            >↓ {{ torrent.downSpeed }}</span
                        >
                        <span class="ml-1 font-bold text-chart-3"
                            >↑ {{ torrent.upSpeed }}</span
                        >
                    </template>
                    <template v-else-if="torrent.statusCode === 6">
                        Seeding to {{ torrent.leechers }} of
                        {{ torrent.peers }} connected peers -
                        <span class="font-bold text-chart-1"
                            >↓ {{ torrent.downSpeed }}</span
                        >
                        <span class="ml-1 font-bold text-chart-3"
                            >↑ {{ torrent.upSpeed }}</span
                        >
                    </template>
                    <template v-else>
                        {{ torrent.status }}
                        <span v-if="torrent.peers > 0"
                            >- {{ torrent.peers }} connected peers</span
                        >
                        -
                        <span class="font-bold text-chart-1"
                            >↓ {{ torrent.downSpeed }}</span
                        >
                        <span class="ml-1 font-bold text-chart-3"
                            >↑ {{ torrent.upSpeed }}</span
                        >
                    </template>
                </div>

                <!-- Progress Bar (Full width background style) -->
                <div
                    class="absolute bottom-0 left-0 h-[2px] w-full bg-background"
                >
                    <div
                        class="h-full transition-all"
                        :class="{
                            'bg-chart-1': torrent.statusCode === 4,
                            'bg-chart-3': torrent.statusCode === 6,
                            'bg-muted-foreground/40': torrent.statusCode === 0,
                            'bg-chart-4': [1, 2, 3, 5].includes(
                                torrent.statusCode,
                            ),
                        }"
                        :style="{ width: torrent.progress + '%' }"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</template>
