<script setup lang="ts">
import { computed } from 'vue';
import {
    Carousel,
    CarouselContent,
    CarouselItem,
    CarouselNext,
    CarouselPrevious,
} from '@/components/ui/carousel';
import { Skeleton } from '@/components/ui/skeleton';
import MediaStatusPill from './MediaStatusPill.vue';

const props = defineProps<{
    serviceItem: any;
    initialData?: any;
    cachedData?: any;
}>();

const jellyfin = computed(() => {
    return {
        ...props.serviceItem,
        ...(props.initialData || props.cachedData || {}),
    };
});

const isLoading = computed(() => !props.initialData && !props.cachedData);

const currentJellyfinRecentlyPlayed = computed(() => {
    if (!jellyfin.value?.recentlyPlayedByUser) {
    }

    const targetUser =
        jellyfin.value.users?.find(
            (u: any) => u.name.toLowerCase() === 'moh',
        ) || jellyfin.value.users?.[0];

    if (!targetUser) {
        return [];
    }

    let items = [...(jellyfin.value.recentlyPlayedByUser[targetUser.id] || [])];

    if (jellyfin.value.nowPlaying && jellyfin.value.nowPlaying[targetUser.id]) {
        const np = jellyfin.value.nowPlaying[targetUser.id];
        items = items.filter((item: any) => item.id !== np.id);
        items.unshift({
            title: np.title,
            seriesName: np.details,
            episode: np.episode || '',
            image: np.image,
            backdrop: np.backdrop || np.image,
            type: 'now-playing',
            id: 'now-playing-' + Date.now(),
            mediaUrl: np.mediaUrl,
            progress: np.progress,
            isNowPlaying: true,
        });
    }

    return items;
});

const currentJellyfinNextUp = computed(() => {
    if (!jellyfin.value?.nextUpByUser) {
        return [];
    }

    const targetUser =
        jellyfin.value.users?.find(
            (u: any) => u.name.toLowerCase() === 'moh',
        ) || jellyfin.value.users?.[0];

    if (!targetUser) {
        return [];
    }

    let items = [...(jellyfin.value.nextUpByUser[targetUser.id] || [])];

    if (jellyfin.value.nowPlaying && jellyfin.value.nowPlaying[targetUser.id]) {
        const np = jellyfin.value.nowPlaying[targetUser.id];
        items = items.filter((item: any) => item.id !== np.id);
    }

    const recentlyPlayedIds = (
        jellyfin.value.recentlyPlayedByUser[targetUser.id] || []
    ).map((item: any) => item.id);
    items = items.filter((item: any) => !recentlyPlayedIds.includes(item.id));

    return items;
});

const openUrl = (url?: string) => {
    if (url) {
        window.open(url, '_blank');
    }
};
</script>

<template>
    <div
        v-if="isLoading"
        class="flex w-full flex-col gap-4 overflow-hidden p-4"
    >
        <!-- Header Skeleton -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <Skeleton class="h-5 w-5 rounded" />
                <Skeleton class="h-4 w-16" />
            </div>
            <Skeleton class="hidden h-5 w-20 rounded-full @sm/widget:block" />
        </div>

        <!-- Continue Watching Skeleton -->
        <div class="pt-1">
            <div class="mt-4 mb-3 flex items-center justify-between">
                <div>
                    <Skeleton class="mb-1.5 h-3 w-28" />
                    <Skeleton class="h-2 w-40" />
                </div>
                <div class="flex items-center gap-1">
                    <Skeleton class="h-6 w-6 rounded-md" />
                    <Skeleton class="h-6 w-6 rounded-md" />
                </div>
            </div>
            <!-- Mock Carousel -->
            <div class="flex w-full gap-2 overflow-hidden widget-md:gap-4">
                <Skeleton
                    class="aspect-[16/9] w-[12rem] shrink-0 rounded-xl @sm/widget:w-[15rem] widget-md:w-[18rem]"
                />
                <Skeleton
                    class="aspect-[16/9] w-[12rem] shrink-0 rounded-xl @sm/widget:w-[15rem] widget-md:w-[18rem]"
                />
                <Skeleton
                    class="aspect-[16/9] w-[12rem] shrink-0 rounded-xl @sm/widget:w-[15rem] widget-md:w-[18rem]"
                />
            </div>
        </div>

        <!-- Next Up Skeleton -->
        <div class="pt-2">
            <div class="mt-4 mb-3 flex items-center justify-between">
                <div>
                    <Skeleton class="mb-1.5 h-3 w-20" />
                    <Skeleton class="h-2 w-32" />
                </div>
                <div class="flex items-center gap-1">
                    <Skeleton class="h-6 w-6 rounded-md" />
                    <Skeleton class="h-6 w-6 rounded-md" />
                </div>
            </div>
            <!-- Mock Carousel -->
            <div class="flex w-full gap-2 overflow-hidden widget-md:gap-4">
                <Skeleton
                    class="aspect-[16/9] w-[12rem] shrink-0 rounded-xl @sm/widget:w-[15rem] widget-md:w-[18rem]"
                />
                <Skeleton
                    class="aspect-[16/9] w-[12rem] shrink-0 rounded-xl @sm/widget:w-[15rem] widget-md:w-[18rem]"
                />
                <Skeleton
                    class="aspect-[16/9] w-[12rem] shrink-0 rounded-xl @sm/widget:w-[15rem] widget-md:w-[18rem]"
                />
            </div>
        </div>
    </div>
    <template v-else>
        <!-- Jellyfin Block -->
        <div
            class="flex w-full flex-col overflow-hidden"
            style="padding: var(--card-padding); gap: var(--card-gap)"
        >
            <div class="flex items-center justify-between">
                <a
                    :href="jellyfin.url"
                    target="_blank"
                    class="group flex cursor-pointer items-center gap-2.5"
                >
                    <img
                        :src="jellyfin.icon"
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
                            {{ jellyfin.name ?? 'Jellyfin' }}
                        </h2>
                        <span
                            class="-mt-0.5 max-w-[200px] truncate text-muted-foreground/80"
                            style="font-size: var(--header-url-size)"
                            >{{
                                jellyfin.url?.replace(/^https?:\/\//, '')
                            }}</span
                        >
                    </div>
                </a>
                <MediaStatusPill :fallback="jellyfin.status" />
            </div>

            <div>
                <!-- Horizontal Poster Carousel using Shadcn -->
                <div
                    v-if="currentJellyfinRecentlyPlayed?.length"
                    class="group/carousel relative"
                >
                    <Carousel :opts="{ align: 'start' }" class="w-full">
                        <div class="mb-3 flex items-center justify-between">
                            <div>
                                <h3
                                    class="text-[11px] font-bold tracking-wider text-muted-foreground uppercase"
                                >
                                    Continue Watching
                                </h3>
                                <p
                                    class="mt-0.5 text-[10px] text-muted-foreground"
                                >
                                    Showing
                                    {{ currentJellyfinRecentlyPlayed.length }}
                                    items to continue watching
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <CarouselPrevious
                                    class="static inset-0 h-6 w-6 translate-x-0 translate-y-0 rounded-md bg-background opacity-100 hover:bg-muted"
                                />
                                <CarouselNext
                                    class="static inset-0 h-6 w-6 translate-x-0 translate-y-0 rounded-md bg-background opacity-100 hover:bg-muted"
                                />
                            </div>
                        </div>
                        <CarouselContent class="-ml-2 widget-md:-ml-4">
                            <CarouselItem
                                v-for="item in currentJellyfinRecentlyPlayed"
                                :key="item.title"
                                class="basis-[12rem] pl-2 @sm/widget:basis-[15rem] widget-md:basis-[18rem] widget-md:pl-4"
                            >
                                <div
                                    @click="openUrl(item.mediaUrl)"
                                    class="group relative aspect-[16/9] scale-100 transform-gpu cursor-pointer overflow-hidden rounded-xl bg-card shadow ring-1 ring-border/20 transition duration-300 outline-none hover:shadow-md hover:ring-border/50"
                                >
                                    <img
                                        :src="item.backdrop"
                                        class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                                        alt=""
                                    />

                                    <!-- Playing Now Badge at Top -->
                                    <div
                                        v-if="item.isNowPlaying"
                                        class="pointer-events-none absolute top-2 left-2 z-30"
                                    >
                                        <span
                                            class="flex items-center gap-1.5 rounded border border-chart-1/50 bg-chart-1/80 px-1.5 py-0.5 text-[9px] font-bold text-white shadow-lg backdrop-blur-md"
                                        >
                                            <span
                                                class="relative flex h-1.5 w-1.5"
                                            >
                                                <span
                                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"
                                                ></span>
                                                <span
                                                    class="relative inline-flex h-1.5 w-1.5 rounded-full bg-white"
                                                ></span>
                                            </span>
                                            PLAYING NOW
                                        </span>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div
                                        v-if="item.progress > 0"
                                        class="absolute bottom-0 left-0 z-20 h-1 w-full bg-black/50"
                                    >
                                        <div
                                            class="h-full bg-chart-1 shadow-[0_0_8px_rgba(59,130,246,0.8)]"
                                            :style="{
                                                width: item.progress + '%',
                                            }"
                                        ></div>
                                    </div>

                                    <div
                                        class="pointer-events-none absolute inset-0 z-10 bg-gradient-to-t from-black/90 via-black/30 to-transparent"
                                    ></div>

                                    <div
                                        class="pointer-events-none absolute bottom-0 left-0 z-10 flex w-full flex-col gap-0.5 p-2.5 pb-3"
                                    >
                                        <span
                                            class="truncate text-[12px] leading-tight font-bold text-white drop-shadow-md"
                                            >{{
                                                item.seriesName || item.title
                                            }}</span
                                        >
                                        <span
                                            v-if="item.seriesName"
                                            class="truncate text-[10px] font-semibold text-white/80"
                                            >{{ item.episode }} -
                                            {{ item.title }}</span
                                        >
                                        <span
                                            v-else
                                            class="truncate text-[10px] font-semibold text-white/80"
                                            >{{ item.episode }}</span
                                        >
                                    </div>

                                    <!-- Play Button Overlay -->
                                    <div
                                        class="pointer-events-none absolute inset-0 z-30 flex items-center justify-center opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                                    >
                                        <a
                                            :href="item.mediaUrl"
                                            target="_blank"
                                            @click.stop
                                            class="pointer-events-auto flex items-center justify-center rounded-full border border-white/20 bg-black/60 p-2 text-white shadow-xl backdrop-blur-sm transition-transform hover:scale-110 hover:bg-black/80"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="ml-0.5 h-6 w-6"
                                                viewBox="0 0 24 24"
                                                fill="currentColor"
                                            >
                                                <path d="M8 5v14l11-7z" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </CarouselItem>
                        </CarouselContent>
                    </Carousel>
                </div>
                <div
                    v-else
                    class="mt-4 rounded-lg border border-border/20 bg-card/50 p-2 text-center text-xs text-muted-foreground"
                >
                    No items to continue watching.
                </div>
            </div>

            <!-- Next Up Section -->
            <div class="pt-2">
                <div
                    v-if="currentJellyfinNextUp.length"
                    class="group/carousel relative"
                >
                    <Carousel
                        :opts="{ align: 'start', loop: false, dragFree: true }"
                        class="w-full"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <div>
                                <h3
                                    class="flex items-center gap-1.5 text-[11px] font-bold tracking-wider text-muted-foreground uppercase"
                                >
                                    Next Up
                                </h3>
                                <p
                                    class="mt-0.5 text-[10px] text-muted-foreground"
                                >
                                    Showing
                                    {{ currentJellyfinNextUp.length }} next up
                                    items
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <CarouselPrevious
                                    class="static inset-0 h-6 w-6 translate-x-0 translate-y-0 rounded-md bg-background opacity-100 hover:bg-muted"
                                />
                                <CarouselNext
                                    class="static inset-0 h-6 w-6 translate-x-0 translate-y-0 rounded-md bg-background opacity-100 hover:bg-muted"
                                />
                            </div>
                        </div>
                        <CarouselContent class="-ml-2 widget-md:-ml-4">
                            <CarouselItem
                                v-for="item in currentJellyfinNextUp"
                                :key="item.title"
                                class="basis-[12rem] pl-2 @sm/widget:basis-[15rem] widget-md:basis-[18rem] widget-md:pl-4"
                            >
                                <div
                                    @click="openUrl(item.mediaUrl)"
                                    class="group relative aspect-[16/9] scale-100 transform-gpu cursor-pointer overflow-hidden rounded-xl bg-card shadow ring-1 ring-border/20 transition duration-300 outline-none hover:shadow-md hover:ring-border/50"
                                >
                                    <img
                                        :src="item.backdrop"
                                        class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                                        alt=""
                                    />

                                    <!-- Progress Bar -->
                                    <div
                                        v-if="item.progress > 0"
                                        class="absolute bottom-0 left-0 z-20 h-1 w-full bg-black/50"
                                    >
                                        <div
                                            class="h-full bg-chart-1 shadow-[0_0_8px_rgba(59,130,246,0.8)]"
                                            :style="{
                                                width: item.progress + '%',
                                            }"
                                        ></div>
                                    </div>

                                    <div
                                        class="pointer-events-none absolute inset-0 z-10 bg-gradient-to-t from-black/90 via-black/30 to-transparent"
                                    ></div>

                                    <div
                                        class="pointer-events-none absolute bottom-0 left-0 z-10 flex w-full flex-col gap-0.5 p-2.5 pb-3"
                                    >
                                        <span
                                            class="truncate text-[12px] leading-tight font-bold text-white drop-shadow-md"
                                            >{{
                                                item.seriesName || item.title
                                            }}</span
                                        >
                                        <span
                                            v-if="item.seriesName"
                                            class="truncate text-[10px] font-semibold text-white/80"
                                            >{{ item.episode }} -
                                            {{ item.title }}</span
                                        >
                                        <span
                                            v-else
                                            class="truncate text-[10px] font-semibold text-white/80"
                                            >{{ item.episode }}</span
                                        >
                                    </div>

                                    <!-- Play Button Overlay -->
                                    <div
                                        class="pointer-events-none absolute inset-0 z-30 flex items-center justify-center opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                                    >
                                        <a
                                            :href="item.mediaUrl"
                                            target="_blank"
                                            @click.stop
                                            class="pointer-events-auto flex items-center justify-center rounded-full border border-white/20 bg-black/60 p-2 text-white shadow-xl backdrop-blur-sm transition-transform hover:scale-110 hover:bg-black/80"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="ml-0.5 h-6 w-6"
                                                viewBox="0 0 24 24"
                                                fill="currentColor"
                                            >
                                                <path d="M8 5v14l11-7z" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </CarouselItem>
                        </CarouselContent>
                    </Carousel>
                </div>
            </div>
        </div>
    </template>
</template>
