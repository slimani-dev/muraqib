<script setup lang="ts">
import { computed } from 'vue';
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from '@/components/ui/carousel';
import { Skeleton } from '@/components/ui/skeleton';
import MediaStatusPill from './MediaStatusPill.vue';

const props = defineProps<{
    serviceItem: any;
    initialData?: any;
    cachedData?: any;
}>();

const jellyfin = computed(() => {
    return { ...props.serviceItem, ...(props.initialData || props.cachedData || {}) };
});

const isLoading = computed(() => !props.initialData && !props.cachedData);

const currentJellyfinRecentlyPlayed = computed(() => {
    if (!jellyfin.value?.recentlyPlayedByUser) {
}

    const targetUser = jellyfin.value.users?.find((u: any) => u.name.toLowerCase() === 'moh')
        || jellyfin.value.users?.[0];

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
            isNowPlaying: true
        });
    }

    return items;
});

const currentJellyfinNextUp = computed(() => {
    if (!jellyfin.value?.nextUpByUser) {
return [];
}

    const targetUser = jellyfin.value.users?.find((u: any) => u.name.toLowerCase() === 'moh')
        || jellyfin.value.users?.[0];

    if (!targetUser) {
return [];
}

    let items = [...(jellyfin.value.nextUpByUser[targetUser.id] || [])];

    if (jellyfin.value.nowPlaying && jellyfin.value.nowPlaying[targetUser.id]) {
        const np = jellyfin.value.nowPlaying[targetUser.id];
        items = items.filter((item: any) => item.id !== np.id);
    }

    const recentlyPlayedIds = (jellyfin.value.recentlyPlayedByUser[targetUser.id] || []).map((item: any) => item.id);
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
    <div v-if="isLoading" class="flex flex-col gap-4 p-4 w-full overflow-hidden">
        <!-- Header Skeleton -->
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-2">
                <Skeleton class="h-5 w-5 rounded" />
                <Skeleton class="h-4 w-16" />
            </div>
            <Skeleton class="h-5 w-20 rounded-full hidden @sm/widget:block" />
        </div>

        <!-- Continue Watching Skeleton -->
        <div class="pt-1">
            <div class="flex items-center justify-between mb-3 mt-4">
                <div>
                    <Skeleton class="h-3 w-28 mb-1.5" />
                    <Skeleton class="h-2 w-40" />
                </div>
                <div class="flex items-center gap-1">
                    <Skeleton class="h-6 w-6 rounded-md" />
                    <Skeleton class="h-6 w-6 rounded-md" />
                </div>
            </div>
            <!-- Mock Carousel -->
            <div class="flex gap-2 widget-md:gap-4 overflow-hidden w-full">
                <Skeleton class="shrink-0 rounded-xl aspect-[16/9] w-[12rem] @sm/widget:w-[15rem] widget-md:w-[18rem]" />
                <Skeleton class="shrink-0 rounded-xl aspect-[16/9] w-[12rem] @sm/widget:w-[15rem] widget-md:w-[18rem]" />
                <Skeleton class="shrink-0 rounded-xl aspect-[16/9] w-[12rem] @sm/widget:w-[15rem] widget-md:w-[18rem]" />
            </div>
        </div>
        
        <!-- Next Up Skeleton -->
        <div class="pt-2">
            <div class="flex items-center justify-between mb-3 mt-4">
                <div>
                    <Skeleton class="h-3 w-20 mb-1.5" />
                    <Skeleton class="h-2 w-32" />
                </div>
                <div class="flex items-center gap-1">
                    <Skeleton class="h-6 w-6 rounded-md" />
                    <Skeleton class="h-6 w-6 rounded-md" />
                </div>
            </div>
            <!-- Mock Carousel -->
            <div class="flex gap-2 widget-md:gap-4 overflow-hidden w-full">
                <Skeleton class="shrink-0 rounded-xl aspect-[16/9] w-[12rem] @sm/widget:w-[15rem] widget-md:w-[18rem]" />
                <Skeleton class="shrink-0 rounded-xl aspect-[16/9] w-[12rem] @sm/widget:w-[15rem] widget-md:w-[18rem]" />
                <Skeleton class="shrink-0 rounded-xl aspect-[16/9] w-[12rem] @sm/widget:w-[15rem] widget-md:w-[18rem]" />
            </div>
        </div>
    </div>
    <template v-else>
        <!-- Jellyfin Block -->
        <div class="flex flex-col w-full overflow-hidden" style="padding: var(--card-padding); gap: var(--card-gap);">
            
            <div class="flex justify-between items-center">
                <a :href="jellyfin.url" target="_blank" class="flex items-center gap-2.5 group cursor-pointer">
                    <img :src="jellyfin.icon" class="rounded-md shadow-sm group-hover:scale-105 transition-transform" style="width: var(--header-icon-size); height: var(--header-icon-size);" alt="" />
                    <div class="flex flex-col">
                        <h2 class="text-sm font-bold text-foreground group-hover:text-primary transition-colors">{{ jellyfin.name ?? 'Jellyfin' }}</h2>
                        <span class="text-muted-foreground/80 -mt-0.5 truncate max-w-[200px]" style="font-size: var(--header-url-size);">{{ jellyfin.url?.replace(/^https?:\/\//, '') }}</span>
                    </div>
                </a>
                <MediaStatusPill :fallback="jellyfin.status" />
            </div>

            <div>
                <!-- Horizontal Poster Carousel using Shadcn -->
                <div v-if="currentJellyfinRecentlyPlayed?.length" class="relative group/carousel">
                    <Carousel :opts="{ align: 'start' }" class="w-full">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3
                                    class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                                    Continue Watching</h3>
                                <p class="text-[10px] text-muted-foreground mt-0.5">
                                    Showing {{ currentJellyfinRecentlyPlayed.length }} items to continue
                                    watching
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <CarouselPrevious
                                    class="static translate-y-0 translate-x-0 inset-0 h-6 w-6 rounded-md bg-background hover:bg-muted opacity-100" />
                                <CarouselNext
                                    class="static translate-y-0 translate-x-0 inset-0 h-6 w-6 rounded-md bg-background hover:bg-muted opacity-100" />
                            </div>
                        </div>
                        <CarouselContent class="-ml-2 widget-md:-ml-4">
                            <CarouselItem v-for="item in currentJellyfinRecentlyPlayed" :key="item.title"
                                class="pl-2 widget-md:pl-4 basis-[12rem] @sm/widget:basis-[15rem] widget-md:basis-[18rem]">
                                <div @click="openUrl(item.mediaUrl)"
                                    class="group relative aspect-[16/9] transform-gpu cursor-pointer overflow-hidden rounded-xl bg-card outline-none ring-1 transition duration-300 scale-100 shadow ring-border/20 hover:ring-border/50 hover:shadow-md">
                                    <img :src="item.backdrop"
                                        class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                                        alt="" />

                                    <!-- Playing Now Badge at Top -->
                                    <div v-if="item.isNowPlaying"
                                        class="absolute top-2 left-2 z-30 pointer-events-none">
                                        <span
                                            class="flex items-center gap-1.5 rounded bg-chart-1/80 border border-chart-1/50 px-1.5 py-0.5 text-[9px] font-bold text-white shadow-lg backdrop-blur-md">
                                            <span class="relative flex h-1.5 w-1.5">
                                                <span
                                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"></span>
                                                <span
                                                    class="relative inline-flex h-1.5 w-1.5 rounded-full bg-white"></span>
                                            </span>
                                            PLAYING NOW
                                        </span>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div v-if="item.progress > 0"
                                        class="absolute bottom-0 left-0 w-full h-1 bg-black/50 z-20">
                                        <div class="h-full bg-chart-1 shadow-[0_0_8px_rgba(59,130,246,0.8)]"
                                            :style="{ width: item.progress + '%' }"></div>
                                    </div>

                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent z-10 pointer-events-none">
                                    </div>

                                    <div
                                        class="absolute bottom-0 left-0 w-full p-2.5 pb-3 flex flex-col gap-0.5 z-10 pointer-events-none">
                                        <span
                                            class="text-[12px] text-white font-bold leading-tight drop-shadow-md truncate">{{
                                                item.seriesName || item.title }}</span>
                                        <span v-if="item.seriesName"
                                            class="text-[10px] font-semibold text-white/80 truncate">{{
                                                item.episode }} - {{ item.title }}</span>
                                        <span v-else class="text-[10px] font-semibold text-white/80 truncate">{{
                                            item.episode }}</span>
                                    </div>

                                    <!-- Play Button Overlay -->
                                    <div
                                        class="absolute inset-0 z-30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                                        <a :href="item.mediaUrl" target="_blank" @click.stop
                                            class="rounded-full bg-black/60 p-2 text-white backdrop-blur-sm border border-white/20 shadow-xl hover:bg-black/80 hover:scale-110 transition-transform flex items-center justify-center pointer-events-auto">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-0.5"
                                                viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M8 5v14l11-7z" />
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </CarouselItem>
                        </CarouselContent>
                    </Carousel>
                </div>
                <div v-else
                    class="text-xs text-muted-foreground p-2 text-center rounded-lg border border-border/20 bg-card/50 mt-4">
                    No items to continue watching.
                </div>
            </div>

            <!-- Next Up Section -->
            <div class="pt-2">
                <div v-if="currentJellyfinNextUp.length" class="relative group/carousel">
                    <Carousel :opts="{ align: 'start', loop: false, dragFree: true }" class="w-full">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3
                                    class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground flex items-center gap-1.5">
                                    Next Up</h3>
                                <p class="text-[10px] text-muted-foreground mt-0.5">
                                    Showing {{ currentJellyfinNextUp.length }} next up items
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <CarouselPrevious
                                    class="static translate-y-0 translate-x-0 inset-0 h-6 w-6 rounded-md bg-background hover:bg-muted opacity-100" />
                                <CarouselNext
                                    class="static translate-y-0 translate-x-0 inset-0 h-6 w-6 rounded-md bg-background hover:bg-muted opacity-100" />
                            </div>
                        </div>
                        <CarouselContent class="-ml-2 widget-md:-ml-4">
                            <CarouselItem v-for="item in currentJellyfinNextUp" :key="item.title"
                                class="pl-2 widget-md:pl-4 basis-[12rem] @sm/widget:basis-[15rem] widget-md:basis-[18rem]">
                                <div @click="openUrl(item.mediaUrl)"
                                    class="group relative aspect-[16/9] transform-gpu cursor-pointer overflow-hidden rounded-xl bg-card outline-none ring-1 transition duration-300 scale-100 shadow ring-border/20 hover:ring-border/50 hover:shadow-md">
                                    <img :src="item.backdrop"
                                        class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                                        alt="" />

                                    <!-- Progress Bar -->
                                    <div v-if="item.progress > 0"
                                        class="absolute bottom-0 left-0 w-full h-1 bg-black/50 z-20">
                                        <div class="h-full bg-chart-1 shadow-[0_0_8px_rgba(59,130,246,0.8)]"
                                            :style="{ width: item.progress + '%' }"></div>
                                    </div>

                                    <div
                                        class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/30 to-transparent z-10 pointer-events-none">
                                    </div>

                                    <div
                                        class="absolute bottom-0 left-0 w-full p-2.5 pb-3 flex flex-col gap-0.5 z-10 pointer-events-none">
                                        <span
                                            class="text-[12px] text-white font-bold leading-tight drop-shadow-md truncate">{{
                                                item.seriesName || item.title }}</span>
                                        <span v-if="item.seriesName"
                                            class="text-[10px] font-semibold text-white/80 truncate">{{
                                                item.episode }} - {{ item.title }}</span>
                                        <span v-else class="text-[10px] font-semibold text-white/80 truncate">{{
                                            item.episode }}</span>
                                    </div>

                                    <!-- Play Button Overlay -->
                                    <div
                                        class="absolute inset-0 z-30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none">
                                        <a :href="item.mediaUrl" target="_blank" @click.stop
                                            class="rounded-full bg-black/60 p-2 text-white backdrop-blur-sm border border-white/20 shadow-xl hover:bg-black/80 hover:scale-110 transition-transform flex items-center justify-center pointer-events-auto">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-0.5"
                                                viewBox="0 0 24 24" fill="currentColor">
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
