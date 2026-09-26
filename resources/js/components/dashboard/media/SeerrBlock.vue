<script setup lang="ts">
import { usePoll } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from '@/components/ui/carousel';
import { Skeleton } from '@/components/ui/skeleton';

const props = defineProps<{
    serviceItem: any;
    initialData?: any;
    cachedData?: any;
}>();

// Poll Seerr data every 5 minutes
usePoll(300000, { only: ['seerr'] });

const seerr = computed(() => {
    return { ...props.serviceItem, ...(props.initialData || props.cachedData || {}) };
});

const isLoading = computed(() => !props.initialData && !props.cachedData);

const openUrl = (url?: string) => {
    if (url) {
        window.open(url, '_blank');
    }
};
</script>

<template>
    <div v-if="isLoading" class="flex flex-col gap-4 p-4 bg-muted/20 flex-1 w-full overflow-hidden">
        <!-- Header Skeleton -->
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-2">
                <Skeleton class="h-5 w-5 rounded" />
                <Skeleton class="h-4 w-16" />
            </div>
            <Skeleton class="h-5 w-24 rounded-full" />
        </div>

        <!-- Recent Requests Skeleton -->
        <div>
            <div class="flex items-center justify-between mb-3">
                <div>
                    <Skeleton class="h-3 w-24 mb-1.5" />
                    <Skeleton class="h-2 w-32" />
                </div>
                <div class="flex items-center gap-1">
                    <Skeleton class="h-3 w-8 mr-2" />
                    <Skeleton class="h-6 w-6 rounded-md" />
                    <Skeleton class="h-6 w-6 rounded-md" />
                </div>
            </div>

            <div class="flex gap-2 md:gap-4 overflow-hidden w-full">
                <!-- Request Card Skeleton -->
                <div v-for="i in 3" :key="i"
                    class="shrink-0 flex h-28 sm:h-36 w-[12rem] sm:w-[15rem] md:w-[18rem] rounded-xl bg-card border border-border/20 p-3 pr-2">
                    <div class="flex-1 flex flex-col gap-2">
                        <Skeleton class="h-2 w-8" />
                        <Skeleton class="h-4 w-3/4" />
                        <div class="flex items-center gap-2 mt-1">
                            <Skeleton class="h-5 w-5 rounded-full" />
                            <Skeleton class="h-3 w-16" />
                        </div>
                        <div class="mt-auto">
                            <Skeleton class="h-4 w-16 rounded-full" />
                        </div>
                    </div>
                    <Skeleton class="h-full w-20 sm:w-24 rounded-md ml-2 shrink-0" />
                </div>
            </div>
        </div>

        <!-- Recently Added Skeleton -->
        <div class="mt-2">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <Skeleton class="h-3 w-28 mb-1.5" />
                    <Skeleton class="h-2 w-36" />
                </div>
                <div class="flex items-center gap-1">
                    <Skeleton class="h-3 w-8 mr-2" />
                    <Skeleton class="h-6 w-6 rounded-md" />
                    <Skeleton class="h-6 w-6 rounded-md" />
                </div>
            </div>

            <div class="flex gap-2 md:gap-4 overflow-hidden w-full">
                <!-- Poster Card Skeleton -->
                <Skeleton v-for="i in 4" :key="i" class="shrink-0 rounded-xl w-28 sm:w-32 md:w-36 aspect-[2/3]" />
            </div>
        </div>
    </div>
    <template v-else>
        <!-- Overseerr Block -->
        <div class="flex flex-col bg-muted/20 flex-1" style="padding: var(--card-padding); gap: var(--card-gap);">
            <div class="flex justify-between items-center">
                <a :href="seerr.url" target="_blank" class="flex items-center gap-2.5 group cursor-pointer">
                    <img :src="seerr.icon" class="rounded-md shadow-sm group-hover:scale-105 transition-transform" style="width: var(--header-icon-size); height: var(--header-icon-size);" alt="" />
                    <div class="flex flex-col">
                        <h2 class="text-sm font-bold text-foreground group-hover:text-primary transition-colors">Seerr</h2>
                        <span class="text-muted-foreground/80 -mt-0.5 truncate max-w-[200px]" style="font-size: var(--header-url-size);">{{ seerr.url?.replace(/^https?:\/\//, '') }}</span>
                    </div>
                </a>
                <div
                    class="flex items-center gap-1.5 border border-primary/30 bg-primary/10 text-primary rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider">
                    <div class="w-1.5 h-1.5 rounded-full bg-primary shadow-[0_0_8px_rgba(59,130,246,0.6)]">
                    </div>
                    {{ seerr.requests?.length || 0 }} Requests
                </div>
            </div>

            <div>
                <div v-if="seerr.requests?.length" class="relative group/carousel">
                    <Carousel :opts="{ align: 'start', loop: true }" class="w-full">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                                    Recent Requests</h3>
                                <p class="text-[10px] text-muted-foreground mt-0.5">
                                    Showing {{ seerr.requests.length }} out of {{ seerr.totalRequests
                                    }} requests
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <a :href="seerr.url + '/requests?filter=all'" target="_blank"
                                    class="text-[10px] font-bold text-primary hover:underline px-2">More</a>
                                <CarouselPrevious
                                    class="static translate-y-0 translate-x-0 inset-0 h-6 w-6 rounded-md bg-background hover:bg-muted opacity-100" />
                                <CarouselNext
                                    class="static translate-y-0 translate-x-0 inset-0 h-6 w-6 rounded-md bg-background hover:bg-muted opacity-100" />
                            </div>
                        </div>
                        <CarouselContent class="-ml-2 md:-ml-4">
                            <CarouselItem v-for="req in seerr.requests" :key="req.title"
                                class="pl-2 md:pl-4 basis-[12rem] sm:basis-[15rem] md:basis-[18rem]">
                                <div @click="openUrl(req.seerrUrl || seerr.url)"
                                    class="group relative flex h-full w-full transform-gpu cursor-pointer overflow-hidden rounded-xl bg-card outline-none ring-1 transition duration-300 scale-100 shadow ring-border/20 hover:ring-border/50 hover:shadow-md">
                                    <!-- Backdrop -->
                                    <div class="absolute inset-0 z-0">
                                        <img v-if="req.backdrop" :src="req.backdrop"
                                            class="absolute inset-0 h-full w-full object-cover text-transparent transition-transform duration-500"
                                            alt="" />
                                        <div
                                            class="absolute inset-0 bg-gradient-to-br from-black/90 via-black/80 to-black/30">
                                        </div>
                                    </div>

                                    <!-- Details -->
                                    <div class="relative z-10 flex min-w-0 flex-1 flex-col justify-start p-3 pr-2">
                                        <div v-if="req.year" class="text-[10px] font-medium text-white/80">{{
                                            req.year }}</div>
                                        <a class="overflow-hidden overflow-ellipsis whitespace-nowrap text-sm font-bold text-white hover:underline sm:text-base mb-1"
                                            :href="req.seerrUrl || seerr.url" target="_blank" @click.stop>{{
                                                req.title }}</a>

                                        <div class="card-field mb-2">
                                            <a class="group/user flex items-center gap-2" href="#">
                                                <span
                                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-chart-1 text-[9px] font-bold text-white overflow-hidden shadow-sm">
                                                    <img v-if="req.userAvatar" :src="req.userAvatar"
                                                        class="h-full w-full object-cover" />
                                                    <span v-else>{{ req.userName?.[0]?.toUpperCase() }}</span>
                                                </span>
                                                <span
                                                    class="truncate text-xs font-semibold text-white/80 group-hover/user:text-white group-hover/user:underline">{{
                                                        req.userName }}</span>
                                            </a>
                                        </div>

                                        <div v-if="req.season" class="my-0.5 flex items-center text-sm sm:my-1">
                                            <span class="mr-2 text-xs font-bold text-white/80">Season</span>
                                            <span
                                                class="inline-flex items-center rounded-full bg-chart-1/80 border border-chart-1 px-2 py-0.5 text-[10px] font-semibold text-white">{{
                                                    req.season }}</span>
                                        </div>

                                        <div class="mt-1 flex items-center text-sm">
                                            <span class="mr-2 text-xs font-bold text-white/80">Status</span>
                                            <span :class="[
                                                req.status === 'Available' ? 'bg-emerald-500/80 border-emerald-500 text-white' :
                                                    req.status === 'Approved' ? 'bg-chart-3/80 border-chart-3 text-white' :
                                                        req.status === 'Pending' ? 'bg-amber-500/80 border-amber-500 text-white' :
                                                            req.status === 'Processing' ? 'bg-blue-500/80 border-blue-500 text-white' :
                                                                'bg-chart-4/80 border-chart-4 text-white',
                                                'inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold transition'
                                            ]">
                                                {{ req.status }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Poster -->
                                    <div class="relative z-10 shrink-0 p-2 pl-0">
                                        <div
                                            class="relative w-20 sm:w-24 aspect-[2/3] overflow-hidden rounded-md shadow-sm transition-transform duration-300 group-hover/poster:shadow-md group/poster">
                                            <img v-if="req.image" :src="req.image"
                                                class="h-full w-full object-cover text-transparent transition-transform duration-500 group-hover/poster:scale-110"
                                                alt="" />
                                            <div v-else
                                                class="h-full w-full bg-muted flex items-center justify-center text-xs text-muted-foreground p-2 text-center">
                                                {{ req.title }}</div>

                                            <div
                                                class="absolute inset-0 z-30 flex items-center justify-center opacity-0 group-hover/poster:opacity-100 transition-opacity duration-300 pointer-events-none">
                                                <a v-if="req.mediaUrl" :href="req.mediaUrl" target="_blank" @click.stop
                                                    class="rounded-full bg-black/60 p-2 text-white backdrop-blur-sm border border-white/20 shadow-xl hover:bg-black/80 hover:scale-110 transition-transform flex items-center justify-center pointer-events-auto">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 ml-0.5"
                                                        viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M8 5v14l11-7z" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CarouselItem>
                        </CarouselContent>
                    </Carousel>
                </div>
                <div v-else
                    class="text-xs text-muted-foreground p-2 text-center rounded-lg border border-border/20 bg-card/50">
                    No active requests.
                </div>
            </div>

            <div class="mt-2">
                <div v-if="seerr.newShows?.length" class="relative group/carousel">
                    <Carousel :opts="{ align: 'start', loop: true }" class="w-full">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h3 class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
                                    Recently Added</h3>
                                <p class="text-[10px] text-muted-foreground mt-0.5">
                                    Showing {{ seerr.newShows.length }} out of {{ seerr.totalMedia }}
                                    added media
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <a :href="seerr.url" target="_blank"
                                    class="text-[10px] font-bold text-primary hover:underline px-2">More</a>
                                <CarouselPrevious
                                    class="static translate-y-0 translate-x-0 inset-0 h-6 w-6 rounded-md bg-background hover:bg-muted opacity-100" />
                                <CarouselNext
                                    class="static translate-y-0 translate-x-0 inset-0 h-6 w-6 rounded-md bg-background hover:bg-muted opacity-100" />
                            </div>
                        </div>
                        <CarouselContent class="-ml-2 md:-ml-4">
                            <CarouselItem v-for="show in seerr.newShows" :key="show.title"
                                class="pl-2 md:pl-4 basis-auto">
                                <div @click="openUrl(show.seerrUrl || seerr.url)" class="w-28 sm:w-32 md:w-36"
                                    data-testid="title-card">
                                    <div class="relative transform-gpu cursor-pointer overflow-hidden rounded-xl bg-gray-800 bg-cover outline-none ring-1 transition duration-300 scale-100 shadow ring-border/20 group hover:ring-border/50 hover:shadow-md"
                                        style="padding-bottom: 150%;">
                                        <div class="absolute inset-0 h-full w-full overflow-hidden">
                                            <img :src="show.image" alt="" loading="lazy" decoding="async"
                                                class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110" />

                                            <div
                                                class="absolute left-0 right-0 flex items-center justify-between p-2 z-30 transition-opacity duration-300">
                                                <div :class="[
                                                    show.type === 'tv' ? 'border-purple-500/50 bg-purple-600/90' : 'border-blue-500/50 bg-blue-600/90',
                                                    'pointer-events-none self-start rounded-full border shadow-md'
                                                ]">
                                                    <div
                                                        class="flex h-4 items-center px-1.5 py-2 text-center text-[9px] font-bold uppercase tracking-wider text-white sm:h-5">
                                                        {{ show.type === 'tv' ? 'Series' : 'Movie' }}</div>
                                                </div>
                                                <div class="flex flex-col items-center gap-1">
                                                    <div class="pointer-events-none flex"
                                                        v-if="show.status === 'Available'">
                                                        <div
                                                            class="relative inline-flex whitespace-nowrap rounded-full text-[9px] font-semibold leading-5">
                                                            <div
                                                                class="rounded-full shadow-md w-4 sm:w-5 border p-0 bg-green-500/90 border-green-500 text-white">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    viewBox="0 0 20 20" fill="currentColor"
                                                                    aria-hidden="true" data-slot="icon">
                                                                    <path fill-rule="evenodd"
                                                                        d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                                                                        clip-rule="evenodd"></path>
                                                                </svg>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div
                                                class="absolute inset-0 z-20 flex flex-col justify-end p-3 sm:p-4 opacity-0 transition-opacity duration-300 group-hover:opacity-100 bg-gradient-to-t from-black via-black/80 to-transparent">
                                                <div v-if="show.year"
                                                    class="text-[10px] font-medium text-primary mb-0.5">{{
                                                        show.year }}</div>
                                                <div :title="show.title"
                                                    class="text-sm sm:text-base font-bold text-white leading-tight mb-1 sm:mb-2 line-clamp-2">
                                                    {{ show.title }}</div>
                                                <div v-if="show.description"
                                                    class="text-[9px] sm:text-[10px] text-white/70 line-clamp-4 leading-snug">
                                                    {{ show.description }}</div>
                                            </div>

                                            <div
                                                class="absolute inset-0 z-30 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 pb-10 pointer-events-none">
                                                <a v-if="show.mediaUrl" :href="show.mediaUrl" target="_blank"
                                                    @click.stop
                                                    class="rounded-full bg-black/60 p-3 text-white backdrop-blur-sm border border-white/20 shadow-xl hover:bg-black/80 hover:scale-110 transition-transform flex items-center justify-center pointer-events-auto">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 ml-1"
                                                        viewBox="0 0 24 24" fill="currentColor">
                                                        <path d="M8 5v14l11-7z" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CarouselItem>
                        </CarouselContent>
                    </Carousel>
                </div>
                <div v-else
                    class="text-xs text-muted-foreground p-2 text-center rounded-lg border border-border/20 bg-card/50">
                    No recently added media found.
                </div>
            </div>
        </div>
    </template>
</template>
