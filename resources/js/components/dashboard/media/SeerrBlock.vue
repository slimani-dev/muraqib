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

const seerr = computed(() => {
    return {
        ...props.serviceItem,
        ...(props.initialData || props.cachedData || {}),
    };
});

const isLoading = computed(() => !props.initialData && !props.cachedData);

const openUrl = (url?: string) => {
    if (url) {
        window.open(url, '_blank');
    }
};
</script>

<template>
    <div
        v-if="isLoading"
        class="flex w-full flex-1 flex-col gap-4 overflow-hidden bg-muted/20 p-4"
    >
        <!-- Header Skeleton -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <Skeleton class="h-5 w-5 rounded" />
                <Skeleton class="h-4 w-16" />
            </div>
            <Skeleton class="h-5 w-24 rounded-full" />
        </div>

        <!-- Recent Requests Skeleton -->
        <div>
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <Skeleton class="mb-1.5 h-3 w-24" />
                    <Skeleton class="h-2 w-32" />
                </div>
                <div class="flex items-center gap-1">
                    <Skeleton class="mr-2 h-3 w-8" />
                    <Skeleton class="h-6 w-6 rounded-md" />
                    <Skeleton class="h-6 w-6 rounded-md" />
                </div>
            </div>

            <div class="flex w-full gap-2 overflow-hidden widget-md:gap-4">
                <!-- Request Card Skeleton -->
                <div
                    v-for="i in 3"
                    :key="i"
                    class="flex h-28 w-[12rem] shrink-0 rounded-xl border border-border/20 bg-card p-3 pr-2 @sm/widget:h-36 @sm/widget:w-[15rem] widget-md:w-[18rem]"
                >
                    <div class="flex flex-1 flex-col gap-2">
                        <Skeleton class="h-2 w-8" />
                        <Skeleton class="h-4 w-3/4" />
                        <div class="mt-1 flex items-center gap-2">
                            <Skeleton class="h-5 w-5 rounded-full" />
                            <Skeleton class="h-3 w-16" />
                        </div>
                        <div class="mt-auto">
                            <Skeleton class="h-4 w-16 rounded-full" />
                        </div>
                    </div>
                    <Skeleton
                        class="ml-2 h-full w-20 shrink-0 rounded-md @sm/widget:w-24"
                    />
                </div>
            </div>
        </div>

        <!-- Recently Added Skeleton -->
        <div class="mt-2">
            <div class="mb-3 flex items-center justify-between">
                <div>
                    <Skeleton class="mb-1.5 h-3 w-28" />
                    <Skeleton class="h-2 w-36" />
                </div>
                <div class="flex items-center gap-1">
                    <Skeleton class="mr-2 h-3 w-8" />
                    <Skeleton class="h-6 w-6 rounded-md" />
                    <Skeleton class="h-6 w-6 rounded-md" />
                </div>
            </div>

            <div class="flex w-full gap-2 overflow-hidden widget-md:gap-4">
                <!-- Poster Card Skeleton -->
                <Skeleton
                    v-for="i in 4"
                    :key="i"
                    class="aspect-[2/3] w-28 shrink-0 rounded-xl @sm/widget:w-32 widget-md:w-36"
                />
            </div>
        </div>
    </div>
    <template v-else>
        <!-- Overseerr Block -->
        <div
            class="flex flex-1 flex-col bg-muted/20"
            style="padding: var(--card-padding); gap: var(--card-gap)"
        >
            <div class="flex items-center justify-between">
                <a
                    :href="seerr.url"
                    target="_blank"
                    class="group flex cursor-pointer items-center gap-2.5"
                >
                    <img
                        :src="seerr.icon"
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
                            {{ seerr.name ?? 'Seerr' }}
                        </h2>
                        <span
                            class="-mt-0.5 max-w-[200px] truncate text-muted-foreground/80"
                            style="font-size: var(--header-url-size)"
                            >{{ seerr.url?.replace(/^https?:\/\//, '') }}</span
                        >
                    </div>
                </a>
                <MediaStatusPill :fallback="seerr.status" />
            </div>

            <div>
                <div
                    v-if="seerr.requests?.length"
                    class="group/carousel relative"
                >
                    <Carousel
                        :opts="{ align: 'start', loop: true }"
                        class="w-full"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <div>
                                <h3
                                    class="text-[11px] font-bold tracking-wider text-muted-foreground uppercase"
                                >
                                    Recent Requests
                                </h3>
                                <p
                                    class="mt-0.5 text-[10px] text-muted-foreground"
                                >
                                    Showing {{ seerr.requests.length }} out of
                                    {{ seerr.totalRequests }} requests
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <a
                                    :href="seerr.url + '/requests?filter=all'"
                                    target="_blank"
                                    class="px-2 text-[10px] font-bold text-primary hover:underline"
                                    >More</a
                                >
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
                                v-for="req in seerr.requests"
                                :key="req.title"
                                class="basis-[12rem] pl-2 @sm/widget:basis-[15rem] widget-md:basis-[18rem] widget-md:pl-4"
                            >
                                <div
                                    @click="openUrl(req.seerrUrl || seerr.url)"
                                    class="group relative flex h-full w-full scale-100 transform-gpu cursor-pointer overflow-hidden rounded-xl bg-card shadow ring-1 ring-border/20 transition duration-300 outline-none hover:shadow-md hover:ring-border/50"
                                >
                                    <!-- Backdrop -->
                                    <div class="absolute inset-0 z-0">
                                        <img
                                            v-if="req.backdrop"
                                            :src="req.backdrop"
                                            class="absolute inset-0 h-full w-full object-cover text-transparent transition-transform duration-500"
                                            alt=""
                                        />
                                        <div
                                            class="absolute inset-0 bg-gradient-to-br from-black/90 via-black/80 to-black/30"
                                        ></div>
                                    </div>

                                    <!-- Details -->
                                    <div
                                        class="relative z-10 flex min-w-0 flex-1 flex-col justify-start p-3 pr-2"
                                    >
                                        <div
                                            v-if="req.year"
                                            class="text-[10px] font-medium text-white/80"
                                        >
                                            {{ req.year }}
                                        </div>
                                        <a
                                            class="mb-1 overflow-hidden text-sm font-bold overflow-ellipsis whitespace-nowrap text-white hover:underline @sm/widget:text-base"
                                            :href="req.seerrUrl || seerr.url"
                                            target="_blank"
                                            @click.stop
                                            >{{ req.title }}</a
                                        >

                                        <div class="card-field mb-2">
                                            <a
                                                class="group/user flex items-center gap-2"
                                                href="#"
                                            >
                                                <span
                                                    class="flex h-5 w-5 shrink-0 items-center justify-center overflow-hidden rounded-full bg-chart-1 text-[9px] font-bold text-white shadow-sm"
                                                >
                                                    <img
                                                        v-if="req.userAvatar"
                                                        :src="req.userAvatar"
                                                        class="h-full w-full object-cover"
                                                    />
                                                    <span v-else>{{
                                                        req.userName?.[0]?.toUpperCase()
                                                    }}</span>
                                                </span>
                                                <span
                                                    class="truncate text-xs font-semibold text-white/80 group-hover/user:text-white group-hover/user:underline"
                                                    >{{ req.userName }}</span
                                                >
                                            </a>
                                        </div>

                                        <div
                                            v-if="req.season"
                                            class="my-0.5 flex items-center text-sm @sm/widget:my-1"
                                        >
                                            <span
                                                class="mr-2 text-xs font-bold text-white/80"
                                                >Season</span
                                            >
                                            <span
                                                class="inline-flex items-center rounded-full border border-chart-1 bg-chart-1/80 px-2 py-0.5 text-[10px] font-semibold text-white"
                                                >{{ req.season }}</span
                                            >
                                        </div>

                                        <div
                                            class="mt-1 flex items-center text-sm"
                                        >
                                            <span
                                                class="mr-2 text-xs font-bold text-white/80"
                                                >Status</span
                                            >
                                            <span
                                                :class="[
                                                    req.status === 'Available'
                                                        ? 'border-emerald-500 bg-emerald-500/80 text-white'
                                                        : req.status ===
                                                            'Approved'
                                                          ? 'border-chart-3 bg-chart-3/80 text-white'
                                                          : req.status ===
                                                              'Pending'
                                                            ? 'border-amber-500 bg-amber-500/80 text-white'
                                                            : req.status ===
                                                                'Processing'
                                                              ? 'border-blue-500 bg-blue-500/80 text-white'
                                                              : 'border-chart-4 bg-chart-4/80 text-white',
                                                    'inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold transition',
                                                ]"
                                            >
                                                {{ req.status }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Poster -->
                                    <div
                                        class="relative z-10 shrink-0 p-2 pl-0"
                                    >
                                        <div
                                            class="group/poster relative aspect-[2/3] w-20 overflow-hidden rounded-md shadow-sm transition-transform duration-300 group-hover/poster:shadow-md @sm/widget:w-24"
                                        >
                                            <img
                                                v-if="req.image"
                                                :src="req.image"
                                                class="h-full w-full object-cover text-transparent transition-transform duration-500 group-hover/poster:scale-110"
                                                alt=""
                                            />
                                            <div
                                                v-else
                                                class="flex h-full w-full items-center justify-center bg-muted p-2 text-center text-xs text-muted-foreground"
                                            >
                                                {{ req.title }}
                                            </div>

                                            <div
                                                class="pointer-events-none absolute inset-0 z-30 flex items-center justify-center opacity-0 transition-opacity duration-300 group-hover/poster:opacity-100"
                                            >
                                                <a
                                                    v-if="req.mediaUrl"
                                                    :href="req.mediaUrl"
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
                                                        <path
                                                            d="M8 5v14l11-7z"
                                                        />
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
                <div
                    v-else
                    class="rounded-lg border border-border/20 bg-card/50 p-2 text-center text-xs text-muted-foreground"
                >
                    No active requests.
                </div>
            </div>

            <div class="mt-2">
                <div
                    v-if="seerr.newShows?.length"
                    class="group/carousel relative"
                >
                    <Carousel
                        :opts="{ align: 'start', loop: true }"
                        class="w-full"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <div>
                                <h3
                                    class="text-[11px] font-bold tracking-wider text-muted-foreground uppercase"
                                >
                                    Recently Added
                                </h3>
                                <p
                                    class="mt-0.5 text-[10px] text-muted-foreground"
                                >
                                    Showing {{ seerr.newShows.length }} out of
                                    {{ seerr.totalMedia }}
                                    added media
                                </p>
                            </div>
                            <div class="flex items-center gap-1">
                                <a
                                    :href="seerr.url"
                                    target="_blank"
                                    class="px-2 text-[10px] font-bold text-primary hover:underline"
                                    >More</a
                                >
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
                                v-for="show in seerr.newShows"
                                :key="show.title"
                                class="basis-auto pl-2 widget-md:pl-4"
                            >
                                <div
                                    @click="openUrl(show.seerrUrl || seerr.url)"
                                    class="w-28 @sm/widget:w-32 widget-md:w-36"
                                    data-testid="title-card"
                                >
                                    <div
                                        class="group relative scale-100 transform-gpu cursor-pointer overflow-hidden rounded-xl bg-gray-800 bg-cover shadow ring-1 ring-border/20 transition duration-300 outline-none hover:shadow-md hover:ring-border/50"
                                        style="padding-bottom: 150%"
                                    >
                                        <div
                                            class="absolute inset-0 h-full w-full overflow-hidden"
                                        >
                                            <img
                                                :src="show.image"
                                                alt=""
                                                loading="lazy"
                                                decoding="async"
                                                class="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                                            />

                                            <div
                                                class="absolute right-0 left-0 z-30 flex items-center justify-between p-2 transition-opacity duration-300"
                                            >
                                                <div
                                                    :class="[
                                                        show.type === 'tv'
                                                            ? 'border-purple-500/50 bg-purple-600/90'
                                                            : 'border-blue-500/50 bg-blue-600/90',
                                                        'pointer-events-none self-start rounded-full border shadow-md',
                                                    ]"
                                                >
                                                    <div
                                                        class="flex h-4 items-center px-1.5 py-2 text-center text-[9px] font-bold tracking-wider text-white uppercase @sm/widget:h-5"
                                                    >
                                                        {{
                                                            show.type === 'tv'
                                                                ? 'Series'
                                                                : 'Movie'
                                                        }}
                                                    </div>
                                                </div>
                                                <div
                                                    class="flex flex-col items-center gap-1"
                                                >
                                                    <div
                                                        class="pointer-events-none flex"
                                                        v-if="
                                                            show.status ===
                                                            'Available'
                                                        "
                                                    >
                                                        <div
                                                            class="relative inline-flex rounded-full text-[9px] leading-5 font-semibold whitespace-nowrap"
                                                        >
                                                            <div
                                                                class="w-4 rounded-full border border-green-500 bg-green-500/90 p-0 text-white shadow-md @sm/widget:w-5"
                                                            >
                                                                <svg
                                                                    xmlns="http://www.w3.org/2000/svg"
                                                                    viewBox="0 0 20 20"
                                                                    fill="currentColor"
                                                                    aria-hidden="true"
                                                                    data-slot="icon"
                                                                >
                                                                    <path
                                                                        fill-rule="evenodd"
                                                                        d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                                                                        clip-rule="evenodd"
                                                                    ></path>
                                                                </svg>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div
                                                class="absolute inset-0 z-20 flex flex-col justify-end bg-gradient-to-t from-black via-black/80 to-transparent p-3 opacity-0 transition-opacity duration-300 group-hover:opacity-100 @sm/widget:p-4"
                                            >
                                                <div
                                                    v-if="show.year"
                                                    class="mb-0.5 text-[10px] font-medium text-primary"
                                                >
                                                    {{ show.year }}
                                                </div>
                                                <div
                                                    :title="show.title"
                                                    class="mb-1 line-clamp-2 text-sm leading-tight font-bold text-white @sm/widget:mb-2 @sm/widget:text-base"
                                                >
                                                    {{ show.title }}
                                                </div>
                                                <div
                                                    v-if="show.description"
                                                    class="line-clamp-4 text-[9px] leading-snug text-white/70 @sm/widget:text-[10px]"
                                                >
                                                    {{ show.description }}
                                                </div>
                                            </div>

                                            <div
                                                class="pointer-events-none absolute inset-0 z-30 flex items-center justify-center pb-10 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                                            >
                                                <a
                                                    v-if="show.mediaUrl"
                                                    :href="show.mediaUrl"
                                                    target="_blank"
                                                    @click.stop
                                                    class="pointer-events-auto flex items-center justify-center rounded-full border border-white/20 bg-black/60 p-3 text-white shadow-xl backdrop-blur-sm transition-transform hover:scale-110 hover:bg-black/80"
                                                >
                                                    <svg
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        class="ml-1 h-10 w-10"
                                                        viewBox="0 0 24 24"
                                                        fill="currentColor"
                                                    >
                                                        <path
                                                            d="M8 5v14l11-7z"
                                                        />
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
                <div
                    v-else
                    class="rounded-lg border border-border/20 bg-card/50 p-2 text-center text-xs text-muted-foreground"
                >
                    No recently added media found.
                </div>
            </div>
        </div>
    </template>
</template>
