<script setup lang="ts">
import BazarrBlock from './media/BazarrBlock.vue';
import JellyfinBlock from './media/JellyfinBlock.vue';
import RadarrBlock from './media/RadarrBlock.vue';
import SeerrBlock from './media/SeerrBlock.vue';
import SonarrBlock from './media/SonarrBlock.vue';
import TransmissionBlock from './media/TransmissionBlock.vue';

const props = defineProps<{
    service: any;
    jellyfin?: any;
    seerr?: any;
    radarr?: any;
    sonarr?: any;
    bazarr?: any;
    transmission?: any;
    jellyfin_cached?: any;
    seerr_cached?: any;
    radarr_cached?: any;
    sonarr_cached?: any;
    bazarr_cached?: any;
    transmission_cached?: any;
}>();

const getServiceItem = (name: string) => {
    return props.service.items.find((i: any) => i.name === name) || {};
};
</script>

<template>
    <div class="transition-all duration-300">

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
            <!-- Left Column: Media Players (Jellyfin) -->
            <div class="lg:col-span-8 flex flex-col gap-4 overflow-hidden">
                <div
                    class="bg-card border rounded-2xl shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col">
                    <JellyfinBlock :serviceItem="getServiceItem('Jellyfin')" :initialData="jellyfin" :cachedData="jellyfin_cached" />
                </div>
                <div
                    class="bg-card border rounded-2xl shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col">
                    <SeerrBlock :serviceItem="getServiceItem('Seerr')" :initialData="seerr" :cachedData="seerr_cached" />
                </div>
            </div>

            <!-- Right Column: Automation (Radarr, Sonarr, etc.) -->
            <div class="lg:col-span-4 lg:relative flex flex-col gap-4 lg:gap-0 lg:block">
                <div class="flex flex-col gap-4  lg:absolute lg:inset-0 h-full">
                    <div
                        class="bg-card border rounded-2xl shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col shrink-0">
                        <RadarrBlock :serviceItem="getServiceItem('Radarr')" :initialData="radarr" :cachedData="radarr_cached" />
                    </div>
                    <div
                        class="bg-card border rounded-2xl shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col shrink-0">
                        <SonarrBlock :serviceItem="getServiceItem('Sonarr')" :initialData="sonarr" :cachedData="sonarr_cached" />
                    </div>
                    <div
                        class="bg-card border rounded-2xl shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col shrink-0">
                        <BazarrBlock :serviceItem="getServiceItem('Bazarr')" :initialData="bazarr" :cachedData="bazarr_cached" />
                    </div>
                    <div
                        class="bg-card border rounded-2xl shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col flex-1 min-h-0">
                        <TransmissionBlock :serviceItem="getServiceItem('Transmission')" :initialData="transmission" :cachedData="transmission_cached"
                            class="flex-1 min-h-0 h-full" />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
