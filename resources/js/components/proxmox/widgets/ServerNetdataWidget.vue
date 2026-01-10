<script setup lang="ts">
import WidgetTitle from '@/components/dashboard/WidgetTitle.vue';
import { useIntervalFn } from '@vueuse/core';
import axios from 'axios';
import { onMounted, ref } from 'vue';

const props = defineProps({
    netdataUrl: {
        type: String,
        default: 'http://192.168.1.199:19999'
    }
});

const loading = ref(true);
const error = ref<string | null>(null);

const stats = ref({
    cpu: { usage: 0 },
    ram: { usage: 0, total_gb: '0', used_gb: '0' },
    load: { 1: 0, 5: 0, 15: 0 },
    uptime: '',
    disks: [] as { name: string; mount: string; percent: number; used_gb: string; total_gb: string; isWarning: boolean }[]
});

// Helper to format bytes to GB
const toGB = (val: number) => (val * 1024 * 1024 * 1024 / 1024 / 1024 / 1024).toFixed(1); // val is usually GiB in v3, wait check units.
// In v3, units are explicit.
// system.ram -> MiB.
// disk_space -> GiB.

const formatUptime = (seconds: number) => {
    const d = Math.floor(seconds / (3600 * 24));
    const h = Math.floor((seconds % (3600 * 24)) / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    return `${d}d ${h}h ${m}m`;
}

const fetchData = async () => {
    try {
        const response = await axios.get(`${props.netdataUrl}/api/v3/allmetrics?format=json`);
        const data = response.data;

        // 1. CPU (system.cpu) - Dimensions: user, system, iowait, idle, etc.
        // Total = sum of all dimensions. Usage = 100 - idle_percent?
        // Actually dimensions are values. relative to what?
        // "units": "percentage". So values ARE percentages?
        // Let's sum them up to check. user=1.5, system=1.5, iowait=0.5, idle=96.4. Sum ~ 100.
        // So YES, values are percentages.
        const cpuChart = data['system.cpu'];
        if (cpuChart) {
            const idle = cpuChart.dimensions.idle?.value || 0;
            stats.value.cpu.usage = Math.round(100 - idle);
        }

        // 2. RAM (system.ram) - Units: MiB
        const ramChart = data['system.ram'];
        if (ramChart) {
            const free = ramChart.dimensions.free?.value || 0;
            const used = ramChart.dimensions.used?.value || 0;
            const cached = ramChart.dimensions.cached?.value || 0;
            const buffers = ramChart.dimensions.buffers?.value || 0;

            const total = free + used + cached + buffers;
            // We consider 'used' as strictly used application memory? 
            // Or should we include cached? Usually "Used" excludes cached/free.
            stats.value.ram = {
                usage: total > 0 ? Math.round((used / total) * 100) : 0,
                used_gb: (used / 1024).toFixed(1), // MiB -> GiB
                total_gb: (total / 1024).toFixed(1)
            };
        }

        // 3. Load (system.load)
        const loadChart = data['system.load'];
        if (loadChart) {
            stats.value.load = {
                1: parseFloat(loadChart.dimensions.load1?.value?.toFixed(2) || 0),
                5: parseFloat(loadChart.dimensions.load5?.value?.toFixed(2) || 0),
                15: parseFloat(loadChart.dimensions.load15?.value?.toFixed(2) || 0)
            };
        }

        // 4. Uptime (netdata.uptime) - Units: seconds
        const uptimeChart = data['netdata.uptime'];
        if (uptimeChart) {
            stats.value.uptime = formatUptime(uptimeChart.dimensions.uptime?.value || 0);
        }

        // 5. Disks
        const disks = [];
        const parseDisk = (key: string, name: string, mount: string) => {
            const chart = data[key];
            if (chart) {
                // Units: GiB
                const avail = chart.dimensions.avail?.value || 0; // GiB
                const used = chart.dimensions.used?.value || 0;   // GiB
                const reserved = chart.dimensions['reserved for root']?.value || 0; // GiB

                const total = avail + used + reserved;
                const percent = total > 0 ? Math.round((used / total) * 100) : 0;

                disks.push({
                    name,
                    mount,
                    percent,
                    used_gb: used.toFixed(1),
                    total_gb: total.toFixed(1),
                    isWarning: percent > 85
                });
            }
        };

        parseDisk('disk_space./', 'Local Storage', '/');
        parseDisk('disk_space./mnt/data', 'Data Volume', '/mnt/data'); // If exists

        stats.value.disks = disks;
        error.value = null;

    } catch (err: any) {
        console.error(err);
        error.value = "Netdata Unreachable";
    } finally {
        loading.value = false;
    }
};

useIntervalFn(fetchData, 2000);
onMounted(fetchData);

// Circular Progress CSS Calculator
const getCircleOffset = (percentage: number) => {
    const radius = 28;
    // Circumference = 2 * PI * 28 ≈ 175.9
    return 175.9 - (175.9 * percentage) / 100;
};
</script>

<template>
    <div class="flex flex-col gap-4 h-full">
        <WidgetTitle server="Proxmox" title="Node Monitoring">
            <template #icon>
                <span class="material-symbols-outlined text-indigo-400">dns</span>
            </template>
            <template #actions>
                <div v-if="error"
                    class="flex items-center gap-1.5 px-2 py-0.5 bg-red-100 dark:bg-red-900/30 rounded-full">
                    <span class="size-1.5 rounded-full bg-red-500"></span>
                    <span class="text-[10px] font-bold text-red-700 dark:text-red-400">OFFLINE</span>
                </div>
                <div v-else
                    class="flex items-center gap-1.5 px-2 py-0.5 bg-green-100 dark:bg-green-900/30 rounded-full">
                    <span class="size-1.5 rounded-full bg-green-500 animate-pulse"></span>
                    <span class="text-[10px] font-bold text-green-700 dark:text-green-400">LIVE</span>
                </div>
            </template>
        </WidgetTitle>

        <div
            class="flex flex-1 flex-col rounded-xl border border-gray-200 bg-white shadow-sm dark:border-border-dark dark:bg-slate-950 dark:shadow-none p-5 gap-6">

            <div v-if="loading && !stats.cpu.usage"
                class="flex-1 flex items-center justify-center text-slate-400 text-xs">
                Connecting to Netdata...
            </div>
            <div v-else class="flex flex-col gap-6">

                <!-- Top Row: Circles & Load -->
                <div class="flex items-start justify-between">

                    <!-- CPU -->
                    <div class="flex flex-col items-center gap-2">
                        <div class="relative size-16">
                            <svg class="size-full transform -rotate-90">
                                <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="6" fill="transparent"
                                    class="text-slate-100 dark:text-slate-800" />
                                <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="6" fill="transparent"
                                    stroke-dasharray="175.9" :stroke-dashoffset="getCircleOffset(stats.cpu.usage)"
                                    class="text-indigo-500 transition-all duration-1000 ease-out"
                                    stroke-linecap="round" />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center flex-col">
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ stats.cpu.usage
                                }}%</span>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">CPU</span>
                    </div>

                    <!-- RAM -->
                    <div class="flex flex-col items-center gap-2">
                        <div class="relative size-16">
                            <svg class="size-full transform -rotate-90">
                                <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="6" fill="transparent"
                                    class="text-slate-100 dark:text-slate-800" />
                                <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="6" fill="transparent"
                                    stroke-dasharray="175.9" :stroke-dashoffset="getCircleOffset(stats.ram.usage)"
                                    class="text-violet-500 transition-all duration-1000 ease-out"
                                    stroke-linecap="round" />
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center flex-col">
                                <span class="text-xs font-bold text-slate-700 dark:text-slate-200">{{ stats.ram.usage
                                }}%</span>
                            </div>
                        </div>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wide">RAM</span>
                    </div>

                    <!-- Load & Uptime -->
                    <div class="flex flex-col gap-3 items-end">
                        <div class="flex flex-col items-end">
                            <span class="text-[10px] text-slate-400 uppercase tracking-wider font-bold mb-1">Load
                                Avg</span>
                            <div class="flex gap-1">
                                <span
                                    class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-mono text-slate-600 dark:text-slate-300 font-bold">{{
                                        stats.load[1] }}</span>
                                <span
                                    class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-mono text-slate-500 dark:text-slate-400">{{
                                        stats.load[5] }}</span>
                                <span
                                    class="px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-[10px] font-mono text-slate-400 dark:text-slate-500 hidden sm:inline-block">{{
                                        stats.load[15] }}</span>
                            </div>
                        </div>

                        <div class="flex flex-col items-end">
                            <span
                                class="text-[10px] text-slate-400 uppercase tracking-wider font-bold mb-0.5">Uptime</span>
                            <span class="text-xs font-mono font-medium text-slate-600 dark:text-slate-300">{{
                                stats.uptime
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Disk Bars -->
                <div class="flex flex-col gap-3 pt-2 border-t border-gray-100 dark:border-white/5">
                    <div v-for="(disk, idx) in stats.disks" :key="idx" class="flex flex-col gap-1.5">
                        <div class="flex justify-between items-end">
                            <div class="flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[14px] text-slate-400">hard_drive</span>
                                <span class="text-[11px] font-bold text-slate-600 dark:text-slate-300">{{ disk.name
                                }}</span>
                                <span class="text-[9px] text-slate-400 font-mono">({{ disk.mount }})</span>
                            </div>
                            <div class="flex items-end gap-1.5">
                                <span class="text-[10px] text-slate-500">{{ disk.used_gb }} / {{ disk.total_gb }}
                                    GB</span>
                                <span class="text-[11px] font-bold"
                                    :class="disk.isWarning ? 'text-red-500' : 'text-indigo-500'">{{ disk.percent
                                    }}%</span>
                            </div>
                        </div>
                        <div class="h-1.5 w-full bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-1000"
                                :class="disk.isWarning ? 'bg-red-500' : 'bg-indigo-500'"
                                :style="{ width: `${disk.percent}%` }">
                            </div>
                        </div>
                    </div>

                    <div v-if="stats.disks.length === 0" class="text-center text-[10px] text-slate-400 py-1">
                        No disk metrics available.
                    </div>
                </div>

            </div>

        </div>
    </div>
</template>
