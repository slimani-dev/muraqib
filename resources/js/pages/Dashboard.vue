<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DashboardContent from '@/components/dashboard/DashboardContent.vue';
import { dashboard } from '@/routes';
import type { Team } from '@/types';

defineProps<{
    currentTeam?: Team | null;
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
    transmission_cached?: any;
    agenda_cached?: any[];
    agenda?: any[];
    containers?: any[];
    netdata?: any[];
    weather?: any;
    weather_cached?: any;
}>();

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: layoutProps.currentTeam
                    ? dashboard(layoutProps.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head title="Dashboard" />

    <DashboardContent :jellyfin="jellyfin" :seerr="seerr" :radarr="radarr" :sonarr="sonarr" :bazarr="bazarr" :transmission="transmission" :jellyfin_cached="jellyfin_cached" :seerr_cached="seerr_cached" :radarr_cached="radarr_cached" :sonarr_cached="sonarr_cached" :bazarr_cached="bazarr_cached" :transmission_cached="transmission_cached" :agenda="agenda" :agenda_cached="agenda_cached" :containers="containers" :netdata="netdata" :weather="weather" :weather_cached="weather_cached" />
</template>
