<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { router, usePage, usePoll } from '@inertiajs/vue3';
import type { Component } from 'vue';
import { computed, provide, ref } from 'vue';
import { useStatusCheck } from '@/composables/useStatusCheck';
import { dashboardData } from '../data';
import type { WidgetMode } from '../widgets/widgetMode';
import WidgetShell from '../widgets/WidgetShell.vue';
import BazarrBlock from './BazarrBlock.vue';
import JellyfinBlock from './JellyfinBlock.vue';
import type { ConfiguredMediaService } from './mediaWidget';
import { mediaWidgetKey } from './mediaWidget';
import RadarrBlock from './RadarrBlock.vue';
import SeerrBlock from './SeerrBlock.vue';
import SonarrBlock from './SonarrBlock.vue';
import TransmissionBlock from './TransmissionBlock.vue';

export type MediaService = ConfiguredMediaService['type'];

const props = withDefaults(
    defineProps<{
        /** The service type, which picks the block. */
        service: MediaService;
        /** Which service of that type: there can be several (two Jellyfins, ...). */
        serviceId: number;
        initialData?: any;
        cachedData?: any;
        mode?: WidgetMode;
    }>(),
    {
        mode: 'desktop',
    },
);

const blocks: Record<MediaService, Component> = {
    jellyfin: JellyfinBlock,
    seerr: SeerrBlock,
    radarr: RadarrBlock,
    sonarr: SonarrBlock,
    bazarr: BazarrBlock,
    transmission: TransmissionBlock,
};

const names: Record<MediaService, string> = {
    jellyfin: 'Jellyfin',
    seerr: 'Seerr',
    radarr: 'Radarr',
    sonarr: 'Sonarr',
    bazarr: 'Bazarr',
    transmission: 'Transmission',
};

/**
 * Poll intervals. Polls are answered from the server cache, so each app is
 * called at most about once per interval however many tabs are open.
 */
const pollIntervals: Record<MediaService, number> = {
    jellyfin: 60_000,
    seerr: 300_000,
    radarr: 120_000,
    sonarr: 120_000,
    bazarr: 300_000,
    transmission: 15_000,
};

const page = usePage();

/** Null when the service was removed or disabled in the admin panel. */
const configured = computed<ConfiguredMediaService | null>(() => {
    const services = (page.props.media_services ??
        []) as ConfiguredMediaService[];

    return services.find((service) => service.id === props.serviceId) ?? null;
});

const dataProp = `media_${props.serviceId}`;

/** The app's icon from the mock service list. Only the icon: its mock status would hide the loading skeleton. */
const serviceItem = computed<Record<string, any>>(() => {
    const item = dashboardData.services[0].items.find(
        (mock) => mock.name === names[props.service],
    );

    return item ? { icon: item.icon } : {};
});

/** The deferred prop loaded, but the app returned nothing and nothing is cached yet. */
const hasNoData = computed(
    () => props.initialData === null && !props.cachedData,
);

usePoll(pollIntervals[props.service], { only: [dataProp, 'media_services'] });

const { state: status, check } = useStatusCheck(() => configured.value?.status);

const refreshing = ref(false);

/** Reload this app's data, bypassing the server cache (throttled server side). */
const refresh = (): void => {
    refreshing.value = true;
    router.reload({
        only: [dataProp, 'media_services'],
        headers: { 'X-Media-Refresh': String(props.serviceId) },
        onFinish: () => {
            refreshing.value = false;
            check();
        },
    });
};

provide(mediaWidgetKey, { status, refreshing, refresh });
</script>

<template>
    <WidgetShell :mode="mode" class="h-full">
        <div
            class="flex h-full flex-col overflow-hidden rounded-2xl border bg-card shadow-sm transition-all hover:shadow-md"
        >
            <div
                v-if="configured && hasNoData"
                class="flex flex-1 flex-col items-center justify-center gap-2 p-6 text-center"
            >
                <img
                    v-if="serviceItem.icon"
                    :src="serviceItem.icon"
                    alt=""
                    class="h-8 w-8 rounded-md opacity-60"
                />
                <p class="text-sm font-semibold text-foreground">
                    No data from {{ configured.name }} yet
                </p>
                <p class="text-xs text-muted-foreground">
                    It didn't answer. It'll be tried again on the next update.
                </p>
                <button
                    type="button"
                    class="flex cursor-pointer items-center gap-1 text-xs font-medium text-primary hover:underline disabled:opacity-50"
                    :disabled="refreshing"
                    @click="refresh"
                >
                    <Icon
                        icon="lucide:refresh-cw"
                        class="h-3.5 w-3.5"
                        :class="{ 'animate-spin': refreshing }"
                    />
                    Try now
                </button>
            </div>
            <component
                v-else-if="configured"
                :is="blocks[service]"
                :service-item="serviceItem"
                :initial-data="initialData"
                :cached-data="cachedData"
                class="min-h-0 flex-1"
            />
            <div
                v-else
                class="flex flex-1 flex-col items-center justify-center gap-2 p-6 text-center"
            >
                <img
                    v-if="serviceItem.icon"
                    :src="serviceItem.icon"
                    alt=""
                    class="h-8 w-8 rounded-md opacity-60 grayscale"
                />
                <p class="text-sm font-semibold text-foreground">
                    {{ names[service] }} was removed or disabled
                </p>
                <a
                    href="/admin/media-services"
                    class="flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                >
                    <Icon icon="lucide:settings-2" class="h-3.5 w-3.5" />
                    Set up in admin
                </a>
            </div>
        </div>
    </WidgetShell>
</template>
