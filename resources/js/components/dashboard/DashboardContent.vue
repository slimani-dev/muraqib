<script setup lang="ts">
import { useLocalStorage, useDocumentVisibility } from '@vueuse/core';
import { onMounted, ref, onUnmounted, watch } from 'vue';
import { useAgendaStore } from '@/stores/useAgendaStore';
import { dashboardData } from './data';
import DockerContainersCard from './DockerContainersCard.vue';
import InboxCard from './InboxCard.vue';
import MediaAgenda from './MediaAgenda.vue';
import MediaArrStackCard from './MediaArrStackCard.vue';
import NetdataServerCard from './NetdataServerCard.vue';
import NetworkSpeedCard from './NetworkSpeedCard.vue';
import OpenSourceStatsCard from './OpenSourceStatsCard.vue';
import PiHoleCard from './PiHoleCard.vue';
import SectionLabel from './SectionLabel.vue';
import ServiceStackCard from './ServiceStackCard.vue';
import WeatherCard from './WeatherCard.vue';

const props = defineProps<{
    agenda_cached?: any[];
    agenda?: any[];
    containers?: any[];
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
    netdata?: any[];
    weather?: any;
    weather_cached?: any;
}>();

const agendaStore = useAgendaStore();

const netdataServers = ref<any[]>(props.netdata || []);
const loadingNetdata = ref(!props.netdata);
let pollingInterval: any = null;

const netdataTimeframes = useLocalStorage<Record<number, string>>('netdataTimeframes', {});

const updateTimeframe = (serverId: number, timeframe: string) => {
    netdataTimeframes.value[serverId] = timeframe;
    fetchNetdata(); // Immediately refresh with the new timeframe
};

const fetchNetdata = async () => {
    try {
        const queryParams = new URLSearchParams();

        for (const [id, timeframe] of Object.entries(netdataTimeframes.value)) {
            queryParams.append(`timeframes[${id}]`, timeframe);
        }

        const qs = queryParams.toString() ? `?${queryParams.toString()}` : '';
        const response = await fetch(`/api/netdata${qs}`);

        if (response.ok) {
            const data = await response.json();
            netdataServers.value = data.servers || [];
        }
    } catch {
        // Silently ignore network errors during polling
    } finally {
        loadingNetdata.value = false;
    }
};


const visibility = useDocumentVisibility();

onMounted(() => {
    if (props.agenda_cached) {
        agendaStore.setEvents(props.agenda_cached);
    } else if (props.agenda) {
        agendaStore.setEvents(props.agenda);
    }

    watch(() => props.agenda, (newAgenda) => {
        if (newAgenda) {
            agendaStore.setEvents(newAgenda);
        }
    });

    if (!props.netdata || props.netdata.some(s => !s.stats || s.stats.is_partial)) {
        fetchNetdata();
    }

    pollingInterval = setInterval(() => {
        if (visibility.value === 'visible') {
            fetchNetdata();
        }
    }, 3000);
});

onUnmounted(() => {
    if (pollingInterval) {
clearInterval(pollingInterval);
}
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4 text-foreground">
        <div
            class="layout grid grid-cols-1 items-start gap-4 md:grid-cols-[20%_1fr_20%] lg:grid-cols-[22%_1fr_22%] xl:grid-cols-[20%_1fr_20%]">
            <!-- LEFT COLUMN -->
            <aside class="col-left flex flex-col space-y-2.5">
                <MediaAgenda />
                <InboxCard />
                <NetworkSpeedCard />
                <PiHoleCard />
                <OpenSourceStatsCard />
            </aside>

            <!-- MIDDLE COLUMN -->
            <div class="col-mid flex flex-col space-y-2.5">
                <DockerContainersCard :containers="containers" />

                <SectionLabel icon="layers" text="Service Stacks" />
                <template v-for="service in dashboardData.services" :key="service.id">
                    <MediaArrStackCard v-if="service.id === 'media'" :service="service" :jellyfin="jellyfin" :seerr="seerr" :radarr="radarr" :sonarr="sonarr" :bazarr="bazarr" :transmission="transmission" :jellyfin_cached="jellyfin_cached" :seerr_cached="seerr_cached" :radarr_cached="radarr_cached" :sonarr_cached="sonarr_cached" :bazarr_cached="bazarr_cached" :transmission_cached="transmission_cached" />
                    <ServiceStackCard v-else :service="service" />
                </template>

                <SectionLabel icon="package" text="Managed Open Source Projects" class="mt-2" />
                <div class="grid grid-cols-1 gap-2 md:grid-cols-3">
                    <!-- REPOS -->
                    <a v-for="repo in dashboardData.repos" :key="repo.name" href="#"
                        class="block cursor-pointer rounded-xl border bg-card/70 backdrop-blur-xl p-3.5 text-card-foreground shadow-xl transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-2xl">
                        <div class="mb-1.5 flex items-start justify-between">
                            <div class="flex items-center gap-1.5">
                                <i data-lucide="github" class="h-3.5 w-3.5 shrink-0 text-slate-400"></i><span
                                    class="text-[13px] font-bold text-white">{{ repo.name }}</span>
                            </div>
                            <span class="shrink-0 rounded border px-1.5 py-0.5 font-mono text-[11px]" :style="{
                                background: repo.versionBg,
                                color: repo.versionColor,
                                borderColor: repo.versionBorder,
                            }">{{ repo.version }}</span>
                        </div>
                        <p class="mb-2 text-[12px] leading-relaxed text-slate-500">
                            {{ repo.desc }}
                        </p>
                        <div class="flex gap-3 font-mono text-[12px]">
                            <span class="flex items-center gap-1 text-amber-400"><i data-lucide="star"
                                    class="h-3 w-3"></i>{{ repo.stars
                                }}</span>
                            <span class="flex items-center gap-1 text-slate-500"><i data-lucide="git-fork"
                                    class="h-3 w-3"></i>{{
                                repo.forks }}</span>
                            <span class="flex items-center gap-1 text-rose-400"><i data-lucide="circle-dot"
                                    class="h-3 w-3"></i>{{
                                repo.issues }}</span>
                            <span class="ml-auto flex items-center gap-1 text-violet-400"><i
                                    data-lucide="git-pull-request" class="h-3 w-3"></i>{{ repo.prs }}</span>
                        </div>
                    </a>
                </div>
            </div>

            <aside class="col-right flex flex-col space-y-2.5">
                <WeatherCard :weather="weather" :weather_cached="weather_cached" />
                
                <div>
                    <SectionLabel icon="server" text="Infrastructure · Netdata" />
                    <!-- SERVERS -->
                    <template v-if="loadingNetdata && netdataServers.length === 0">
                        <NetdataServerCard :server="{}" :loading="true" />
                        <NetdataServerCard :server="{}" :loading="true" />
                    </template>
                    <template v-else>
                        <NetdataServerCard v-for="server in netdataServers" :key="server.id" :server="server"
                            :timeframe="netdataTimeframes[server.id] || '1h'"
                            @update:timeframe="updateTimeframe(server.id, $event)" />
                    </template>
                </div>

                <div class="rounded-xl border bg-card/70 backdrop-blur-xl p-3.5 text-card-foreground shadow-xl">
                    <SectionLabel icon="inbox" text="Awaiting Review" />
                    <div class="space-y-0.5">
                        <div v-for="pr in dashboardData.pullRequests" :key="pr.title"
                            class="flex items-center justify-between rounded-md p-1.5 transition-colors hover:bg-muted/50">
                            <span class="flex items-center gap-1.5 text-[13px]">
                                <i :data-lucide="pr.icon" class="h-3 w-3 shrink-0" :class="pr.iconColor"></i>
                                <span class="truncate text-slate-300">{{
                                    pr.title
                                    }}</span>
                            </span>
                            <span class="shrink-0 font-mono text-[11px] text-slate-600">{{ pr.time }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card/70 backdrop-blur-xl p-3.5 text-card-foreground shadow-xl">
                    <SectionLabel icon="box" text="Container Status" />
                    <div class="space-y-1.5">
                        <div v-for="c in dashboardData.containers.list" :key="c.name"
                            class="flex items-center justify-between text-[12px]">
                            <span class="flex items-center gap-1.5 text-slate-300"><span
                                    class="relative flex h-1.5 w-1.5">
                                    <span
                                        class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75"
                                        :class="c.dotColor"></span>
                                    <span class="relative inline-flex h-1.5 w-1.5 rounded-full"
                                        :class="c.dotColor"></span>
                                </span>{{ c.name }}</span>
                            <span class="font-mono" :class="c.statusColor">{{
                                c.status
                                }}</span>
                        </div>
                    </div>
                    <div class="mt-2 border-t border-border/10 pt-2 text-center">
                        <span class="font-mono text-[11px] text-slate-600">{{ dashboardData.containers.total }} total ·
                            {{ dashboardData.containers.running }} running ·
                            {{ dashboardData.containers.update }} update</span>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</template>
