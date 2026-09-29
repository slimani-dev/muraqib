<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { router, usePage } from '@inertiajs/vue3';
import { useElementSize } from '@vueuse/core';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import SectionLabel from './SectionLabel.vue';
import type { WidgetMode } from './widgets/widgetMode';
import WidgetShell from './widgets/WidgetShell.vue';

const props = withDefaults(defineProps<{
    weather?: any;
    weather_cached?: any;
    mode?: WidgetMode;
}>(), {
    mode: 'desktop',
});

const page = usePage();
const locationName = computed(() => props.weather?.location_name ?? props.weather_cached?.location_name ?? '');

const currentWeather = computed(() => props.weather || props.weather_cached);
const loading = computed(() => !currentWeather.value);
const refreshing = ref(false);
const isFlipped = ref(false);

const containerRef = ref<HTMLElement | null>(null);
const { width } = useElementSize(containerRef);

const maxVisibleDays = computed(() => {
    if (!width.value) {
return 4;
}

    const availableDataDays = (currentWeather.value?.daily?.time?.length || 5) - 1;
    const availableWidth = width.value - 40;
    const minWidthPerDay = 45;
    const fittedDays = Math.floor(availableWidth / minWidthPerDay);

    return Math.max(1, Math.min(fittedDays, availableDataDays));
});

const refreshBackground = (e?: Event) => {
    if (e) {
e.stopPropagation();
}

    if (refreshing.value) {
return;
}

    refreshing.value = true;

    const teamSlug = (page.props.auth as any).user.current_team.slug;
    router.post(`/${teamSlug}/dashboard/weather/refresh`, {}, {
        preserveScroll: true,
        only: ['weather'],
        onFinish: () => {
            refreshing.value = false;
        }
    });
};

let interval: ReturnType<typeof setInterval>;
const isMounted = ref(false);
onMounted(() => {
    isMounted.value = true;
    interval = setInterval(() => {
        currentTime.value = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false });
    }, 60000);
});

onUnmounted(() => {
    if (interval) {
clearInterval(interval);
}
});

const getWeatherDetails = (code: number, isDay: number = 1) => {
    const codes: Record<number, { text: string, icon: string, bg: string, barBg: string }> = {
        0: { text: "Sunny", icon: "fa6-solid:sun", bg: "bg-sunny-day", barBg: "bg-bar-sunny-day" },
        1: { text: "Mainly Clear", icon: "fa6-solid:sun", bg: "bg-sunny-day", barBg: "bg-bar-sunny-day" },
        2: { text: "Partly Cloudy", icon: "fa6-solid:cloud-sun", bg: "bg-mostly-cloudy", barBg: "bg-bar-mostly-cloudy" },
        3: { text: "Overcast", icon: "fa6-solid:cloud", bg: "bg-cloudy", barBg: "bg-bar-cloudy" },
        45: { text: "Fog", icon: "fa6-solid:smog", bg: "bg-hazy", barBg: "bg-bar-hazy" },
        48: { text: "Depositing Rime Fog", icon: "fa6-solid:smog", bg: "bg-hazy", barBg: "bg-bar-hazy" },
        51: { text: "Drizzle: Light", icon: "fa6-solid:cloud-rain", bg: "bg-rainy", barBg: "bg-bar-rainy" },
        53: { text: "Drizzle: Moderate", icon: "fa6-solid:cloud-rain", bg: "bg-rainy", barBg: "bg-bar-rainy" },
        55: { text: "Drizzle: Dense", icon: "fa6-solid:cloud-rain", bg: "bg-rainy", barBg: "bg-bar-rainy" },
        56: { text: "Freezing Drizzle", icon: "fa6-solid:cloud-meatball", bg: "bg-hail", barBg: "bg-bar-hail" },
        57: { text: "Freezing Drizzle", icon: "fa6-solid:cloud-meatball", bg: "bg-hail", barBg: "bg-bar-hail" },
        61: { text: "Rain: Slight", icon: "fa6-solid:cloud-rain", bg: "bg-rainy", barBg: "bg-bar-rainy" },
        63: { text: "Rain: Moderate", icon: "fa6-solid:cloud-rain", bg: "bg-rainy", barBg: "bg-bar-rainy" },
        65: { text: "Rain: Heavy", icon: "fa6-solid:cloud-showers-heavy", bg: "bg-heavy-rain", barBg: "bg-bar-heavy-rain" },
        66: { text: "Freezing Rain", icon: "fa6-solid:cloud-meatball", bg: "bg-hail", barBg: "bg-bar-hail" },
        67: { text: "Freezing Rain", icon: "fa6-solid:cloud-meatball", bg: "bg-hail", barBg: "bg-bar-hail" },
        71: { text: "Snow: Slight", icon: "fa6-solid:snowflake", bg: "bg-snow", barBg: "bg-bar-snow" },
        73: { text: "Snow: Moderate", icon: "fa6-solid:snowflake", bg: "bg-snow", barBg: "bg-bar-snow" },
        75: { text: "Snow: Heavy", icon: "fa6-solid:snowflake", bg: "bg-heavy-snow", barBg: "bg-bar-heavy-snow" },
        77: { text: "Snow Grains", icon: "fa6-solid:snowflake", bg: "bg-snow", barBg: "bg-bar-snow" },
        80: { text: "Rain Showers", icon: "fa6-solid:cloud-rain", bg: "bg-rainy", barBg: "bg-bar-rainy" },
        81: { text: "Rain Showers", icon: "fa6-solid:cloud-showers-heavy", bg: "bg-heavy-rain", barBg: "bg-bar-heavy-rain" },
        82: { text: "Heavy Rain Showers", icon: "fa6-solid:cloud-showers-heavy", bg: "bg-heavy-rain", barBg: "bg-bar-heavy-rain" },
        85: { text: "Snow Showers", icon: "fa6-solid:snowflake", bg: "bg-snow", barBg: "bg-bar-snow" },
        86: { text: "Heavy Snow Showers", icon: "fa6-solid:snowflake", bg: "bg-heavy-snow", barBg: "bg-bar-heavy-snow" },
        95: { text: "Thunderstorm", icon: "fa6-solid:cloud-bolt", bg: "bg-heavy-rain", barBg: "bg-bar-heavy-rain" },
        96: { text: "Thunderstorm with Hail", icon: "fa6-solid:cloud-bolt", bg: "bg-hail", barBg: "bg-bar-hail" },
        99: { text: "Thunderstorm with Heavy Hail", icon: "fa6-solid:cloud-bolt", bg: "bg-hail", barBg: "bg-bar-hail" },
    };

    let details = codes[code] || codes[0];

    if (!isDay && (code === 0 || code === 1 || code === 2)) {
        details = { ...details, icon: code === 2 ? "fa6-solid:cloud-moon" : "fa6-solid:moon", bg: "bg-sunny-night", barBg: "bg-bar-sunny-night", text: code === 2 ? "Partly Cloudy" : "Clear" };
    }

    return details;
};

const getDayName = (dateStr: string) => {
    const days = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];
    const d = new Date(dateStr);

    return days[d.getDay()];
};

const formatDate = (dateStr: string) => {
    const d = new Date(dateStr);
    const month = (d.getMonth() + 1).toString().padStart(2, '0');
    const day = d.getDate().toString().padStart(2, '0');

    return `${month}-${day}`;
};

const currentTime = ref(new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: false }));
const temperatureUnit = ref<'C' | 'F'>('C');
const activeTab = ref<'temperature' | 'precipitation' | 'wind'>('temperature');

const formatTemp = (celsius: number) => {
    if (temperatureUnit.value === 'C') {
return Math.round(celsius);
}

    return Math.round((celsius * 9 / 5) + 32);
};

// Compute exactly 8 points (every 3 hours) for our custom charts
const eightPoints = computed(() => {
    if (!currentWeather.value?.hourly) {
return [];
}

    const times = currentWeather.value.hourly.time;
    const temps = currentWeather.value.hourly.temperature_2m;
    const precip = currentWeather.value.hourly.precipitation_probability;
    const wind = currentWeather.value.hourly.wind_speed_10m;
    const windDir = currentWeather.value.hourly.wind_direction_10m;

    const pts = [];

    // Start at current hour and step by 3 hours for 8 points total
    for (let i = 0; i < 24; i += 3) {
        const d = new Date(times[i]);
        pts.push({
            index: i,
            time: d.toLocaleTimeString('en-US', { hour: 'numeric', hour12: true }).replace(':00', ''), // e.g. "5 AM"
            temp: temps[i],
            tempFormatted: formatTemp(temps[i]),
            precip: precip[i] ?? 0,
            wind: Math.round(wind[i] ?? 0),
            windDir: (windDir && windDir[i]) ?? 0,
        });
    }

    return pts;
});

// Compute SVG paths for smooth temperature area chart
const tempSvgPaths = computed(() => {
    if (!eightPoints.value.length) {
return { line: '', area: '', points: [] };
}

    const pts = eightPoints.value;
    const minT = Math.min(...pts.map(p => p.temp)) - 3;
    const maxT = Math.max(...pts.map(p => p.temp)) + 3;
    const range = maxT - minT || 1;

    const mapped = pts.map((p, i) => {
        const x = (i / 7) * 100; // 0 to 100
        const y = 100 - ((p.temp - minT) / range) * 80; // 20 to 100 to leave room for text

        return { x, y, label: p.tempFormatted, time: p.time };
    });

    // Smooth bezier curve generator
    let d = `M ${mapped[0].x},${mapped[0].y}`;

    for (let i = 0; i < mapped.length - 1; i++) {
        const curr = mapped[i];
        const next = mapped[i + 1];
        const midX = (curr.x + next.x) / 2;
        d += ` C ${midX},${curr.y} ${midX},${next.y} ${next.x},${next.y}`;
    }

    const area = `${d} L 100,100 L 0,100 Z`;

    return { line: d, area, points: mapped };
});
</script>

<template>
    <WidgetShell v-slot="{ layout }" :mode="mode" class="mb-4">
        <SectionLabel icon="cloud-sun" text="Weather" />
        <div v-if="loading" class="w-full aspect-video rounded-2xl border bg-card/70 shadow-sm animate-pulse widget-md:aspect-[21/9] widget-lg:aspect-[32/9]"></div>

        <div v-else-if="currentWeather"
            class="w-full aspect-video perspective-[1000px] cursor-pointer group [container-type:size] [container-name:weather] widget-md:aspect-[21/9] widget-lg:aspect-[32/9] widget-lg:cursor-default"
            @click="layout !== 'desktop' && (isFlipped = !isFlipped)">

            <div class="relative w-full h-full transition-transform duration-700 [transform-style:preserve-3d] widget-lg:grid widget-lg:grid-cols-2 widget-lg:gap-4 widget-lg:[transform-style:flat]"
                :class="{ '[transform:rotateY(180deg)]': isFlipped && layout !== 'desktop' }">

                <!-- Front Face -->
                <div ref="containerRef"
                    class="absolute inset-0 rounded-2xl overflow-hidden shadow-sm transition-all duration-300 text-white flex flex-col border border-border/50 bg-cover bg-center [backface-visibility:hidden] [-webkit-backface-visibility:hidden] [transform:translateZ(1px)] widget-lg:relative widget-lg:inset-auto widget-lg:[transform:none]"
                    :class="[!currentWeather.background_image ? getWeatherDetails(currentWeather.current.weather_code, currentWeather.current.is_day).bg : '']"
                    :style="currentWeather.background_image ? `background-image: url('${currentWeather.background_image}')` : ''">

                    <div v-if="currentWeather.background_image"
                        class="absolute inset-0 bg-gradient-to-b from-black/50 via-black/10 to-black/60 pointer-events-none">
                    </div>

                    <!-- Main Content Area -->
                    <div
                        class="p-3 sm:p-5 sm:pb-0 relative z-10 flex-1 flex flex-col min-h-0 [transform:translateZ(2px)]">
                        <!-- Top Header -->
                        <div class="flex justify-between items-start w-full shrink-0">
                            <div
                                class="flex items-center gap-1.5 sm:gap-2 text-xs sm:text-sm font-medium drop-shadow-md overflow-hidden">
                                <Icon
                                    :icon="getWeatherDetails(currentWeather.current.weather_code, currentWeather.current.is_day).icon"
                                    class="h-3 w-3 sm:h-4 sm:w-4 shrink-0" />
                                <span class="truncate">{{ getWeatherDetails(currentWeather.current.weather_code,
                                    currentWeather.current.is_day).text }}</span>
                            </div>
                            <div class="flex items-start gap-2 sm:gap-3 text-right drop-shadow-md shrink-0 ml-2">
                                <button @click.stop="refreshBackground"
                                    class="opacity-50 hover:opacity-100 transition-opacity p-1 -mt-1 hover:bg-white/10 rounded-full shrink-0"
                                    title="Get new background">
                                    <Icon icon="fa6-solid:arrows-rotate" class="w-3 h-3 sm:w-3.5 sm:h-3.5"
                                        :class="{ 'animate-spin': refreshing }" />
                                </button>
                                <div class="flex flex-col items-end">
                                    <div class="text-base sm:text-xl font-medium leading-none mb-0.5 sm:mb-1">{{
                                        currentTime }}</div>
                                    <div class="text-[9px] sm:text-xs opacity-90 uppercase tracking-wide leading-none">
                                        {{ getDayName(currentWeather.current.time) }} {{
                                            formatDate(currentWeather.current.time) }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Middle Content (Temp & Location) - Pushes down -->
                        <div class="flex-1 flex flex-col justify-end w-full pb-1 sm:pb-2 min-h-0">
                            <div class="flex justify-between items-end drop-shadow-md">
                                <div class="flex flex-col shrink-0">
                                    <div class="text-[clamp(1.5rem,calc(85cqh-108px),7rem)] font-semibold leading-none mb-1">{{
                                        Math.round(currentWeather.current.temperature_2m) }}°</div>
                                    <div class="text-[9px] sm:text-xs opacity-90 font-medium tracking-wide">
                                        {{ Math.round(currentWeather.daily.temperature_2m_max[0]) }}° / {{
                                            Math.round(currentWeather.daily.temperature_2m_min[0]) }}°
                                    </div>
                                </div>
                                <div class="text-[10px] sm:text-sm font-medium text-right truncate pl-2">{{ locationName
                                }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Daily forecast Bottom Bar -->
                    <div class="px-3 sm:px-5 pb-1.5 sm:pb-2.5 pt-0 flex justify-between text-xs font-medium uppercase tracking-wider relative z-10 shrink-0 [transform:translateZ(2px)]"
                        :class="[!currentWeather.background_image ? getWeatherDetails(currentWeather.current.weather_code, currentWeather.current.is_day).barBg + ' backdrop-blur-md shadow-inner' : 'bg-transparent']">
                        <template v-if="isMounted">
                            <template v-for="i in maxVisibleDays" :key="i">
                                <div
                                    class="flex flex-col items-center opacity-90 hover:opacity-100 transition-opacity drop-shadow-sm min-w-[24px] sm:min-w-[30px]">
                                    <span class="mb-1 sm:mb-1.5 text-[8px] sm:text-[10px] font-bold">{{
                                        getDayName(currentWeather.daily.time[i]) }}</span>
                                    <Icon :icon="getWeatherDetails(currentWeather.daily.weather_code[i], 1).icon"
                                        class="h-3 w-3 sm:h-4 sm:w-4 shrink-0 mb-0.5 sm:mb-1" />
                                    <div class="flex items-center gap-0.5 sm:gap-1 text-[7px] sm:text-[8px]">
                                        <span class="font-bold">{{
                                            Math.round(currentWeather.daily.temperature_2m_max[i]) }}°</span>
                                        <span class="opacity-70">{{
                                            Math.round(currentWeather.daily.temperature_2m_min[i]) }}°</span>
                                    </div>
                                </div>
                            </template>
                        </template>
                        <!-- Invisible placeholder to preserve exact height on SSR to prevent layout jumping -->
                        <template v-else>
                            <div class="flex flex-col items-center invisible min-w-[24px] sm:min-w-[30px]">
                                <span class="mb-1 sm:mb-1.5 text-[8px] sm:text-[10px] font-bold">SUN</span>
                                <Icon icon="fa6-solid:sun" class="h-3 w-3 sm:h-4 sm:w-4 shrink-0 mb-0.5 sm:mb-1" />
                                <div class="flex items-center gap-0.5 sm:gap-1 text-[7px] sm:text-[8px]">
                                    <span class="font-bold">0°</span>
                                    <span class="opacity-70">0°</span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Back Face -->
                <div class="absolute inset-0 rounded-2xl overflow-hidden shadow-sm transition-all duration-300 text-white flex flex-col border border-border/50 bg-cover bg-center [backface-visibility:hidden] [-webkit-backface-visibility:hidden] [transform:rotateY(180deg)_translateZ(1px)] widget-lg:relative widget-lg:inset-auto widget-lg:[transform:none]"
                    :class="[!currentWeather.background_image ? getWeatherDetails(currentWeather.current.weather_code, currentWeather.current.is_day).bg : '']"
                    :style="currentWeather.background_image ? `background-image: url('${currentWeather.background_image}')` : ''">

                    <div v-if="currentWeather.background_image"
                        class="absolute inset-0 bg-black/85 pointer-events-none backdrop-blur-xl"></div>
                    <div v-else class="absolute inset-0 bg-black/40 pointer-events-none backdrop-blur-lg"></div>

                    <div
                        class="p-4 sm:p-5 relative z-10 flex-1 flex flex-col min-h-0 h-full w-full [transform:translateZ(2px)]">
                        <!-- Header: Metrics Only -->
                        <div class="flex flex-row justify-between w-full text-[10px] sm:text-xs opacity-90">
                            <div>Precipitation: <span class="font-semibold text-white">{{
                                currentWeather.current.precipitation_probability ?? 0 }}%</span></div>
                            <div>Humidity: <span class="font-semibold text-white">{{
                                currentWeather.current.relative_humidity_2m ?? 0
                                    }}%</span></div>
                            <div>Wind: <span class="font-semibold text-white">{{ currentWeather.current.wind_speed_10m
                                ?? 0 }}
                                    km/h</span></div>
                        </div>

                        <!-- Chart Tabs -->
                        <div
                            class="flex w-full mt-auto text-[10px] sm:text-xs border-b border-white/20 shrink-0 mb-2 sm:mb-3">
                            <button @click.stop="activeTab = 'temperature'"
                                :class="{ 'border-b-2 border-yellow-400 text-yellow-400 font-medium': activeTab === 'temperature', 'opacity-60 hover:opacity-100': activeTab !== 'temperature' }"
                                class="flex-1 text-center pb-1 transition-all">Temperature</button>
                            <button @click.stop="activeTab = 'precipitation'"
                                :class="{ 'border-b-2 border-blue-400 text-blue-400 font-medium': activeTab === 'precipitation', 'opacity-60 hover:opacity-100': activeTab !== 'precipitation' }"
                                class="flex-1 text-center pb-1 transition-all">Precipitation</button>
                            <button @click.stop="activeTab = 'wind'"
                                :class="{ 'border-b-2 border-white opacity-100 font-medium': activeTab === 'wind', 'opacity-60 hover:opacity-100': activeTab !== 'wind' }"
                                class="flex-1 text-center pb-1 transition-all">Wind</button>
                        </div>

                        <!-- Custom Charts Area -->
                        <div class="w-full h-16 sm:h-20 relative select-none">

                            <!-- Temperature Area Chart -->
                            <div v-if="activeTab === 'temperature'" class="absolute inset-0 w-full h-full">
                                <svg class="w-full h-full overflow-visible" viewBox="0 0 100 100"
                                    preserveAspectRatio="none">
                                    <path :d="tempSvgPaths.area" fill="rgba(234, 179, 8, 0.25)"
                                        class="transition-all duration-500" />
                                    <path :d="tempSvgPaths.line" fill="none" stroke="#facc15" stroke-width="2"
                                        vector-effect="non-scaling-stroke" class="transition-all duration-500" />
                                </svg>
                                <div class="absolute inset-0">
                                    <div v-for="(pt, i) in tempSvgPaths.points" :key="i"
                                        class="absolute flex flex-col items-center -translate-x-1/2 -translate-y-full pb-1"
                                        :style="{ left: pt.x + '%', top: pt.y + '%' }">
                                        <span class="text-[9px] sm:text-[10px] font-bold text-white drop-shadow-md">{{
                                            pt.label
                                        }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Precipitation Bar Chart -->
                            <div v-else-if="activeTab === 'precipitation'"
                                class="absolute inset-0 w-full h-full flex items-end justify-between px-1">
                                <div v-for="(pt, i) in eightPoints" :key="i"
                                    class="flex flex-col items-center h-full justify-end w-[10%]">
                                    <span class="text-[8px] sm:text-[9px] text-blue-300 font-bold mb-0.5 sm:mb-1">{{
                                        pt.precip
                                    }}%</span>
                                    <div class="w-full bg-blue-500/20 border-t border-blue-400 rounded-t-[1px] transition-all duration-500 min-h-[2px]"
                                        :style="{ height: `${Math.max(pt.precip, 2)}%` }"></div>
                                    <span class="text-[7px] sm:text-[8px] text-white/50 mt-1 whitespace-nowrap">{{
                                        pt.time }}</span>
                                </div>
                            </div>

                            <!-- Wind Direction Row -->
                            <div v-else-if="activeTab === 'wind'"
                                class="absolute inset-0 w-full h-full flex items-center justify-between px-1">
                                <div v-for="(pt, i) in eightPoints" :key="i"
                                    class="flex flex-col items-center justify-center gap-1 sm:gap-2">
                                    <span class="text-[8px] sm:text-[9px] font-medium">{{ pt.wind }} km/h</span>
                                    <Icon icon="fa6-solid:arrow-down" :style="{ transform: `rotate(${pt.windDir}deg)` }"
                                        class="w-3 h-3 sm:w-4 sm:h-4 text-white/80" />
                                    <span class="text-[7px] sm:text-[8px] text-white/50 whitespace-nowrap">{{ pt.time
                                    }}</span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </WidgetShell>
</template>

<style scoped>
.bg-sunny-day {
    background: linear-gradient(135deg, #f06966 0%, #fad961 100%);
}

.bg-sunny-night {
    background: linear-gradient(135deg, #4b5d8f 0%, #2f3b5c 100%);
}

.bg-rainy {
    background: linear-gradient(135deg, #7c5295 0%, #463b78 100%);
}

.bg-heavy-rain {
    background: linear-gradient(135deg, #444c68 0%, #262c40 100%);
}

.bg-cloudy {
    background: linear-gradient(135deg, #5eb2f5 0%, #3e88d6 100%);
}

.bg-mostly-cloudy {
    background: linear-gradient(135deg, #5283c4 0%, #295393 100%);
}

.bg-snow {
    background: linear-gradient(135deg, #43bbf6 0%, #1a96e2 100%);
}

.bg-heavy-snow {
    background: linear-gradient(135deg, #2b7cc8 0%, #155598 100%);
}

.bg-hazy {
    background: linear-gradient(135deg, #faa255 0%, #e27d31 100%);
}

.bg-hail {
    background: linear-gradient(135deg, #373b53 0%, #1e2030 100%);
}

.bg-bar-sunny-day {
    background-color: rgba(186, 68, 83, 0.5);
}

.bg-bar-sunny-night {
    background-color: rgba(43, 80, 143, 0.5);
}

.bg-bar-rainy {
    background-color: rgba(71, 95, 196, 0.5);
}

.bg-bar-heavy-rain {
    background-color: rgba(69, 58, 128, 0.5);
}

.bg-bar-cloudy {
    background-color: rgba(56, 128, 196, 0.5);
}

.bg-bar-mostly-cloudy {
    background-color: rgba(29, 65, 116, 0.5);
}

.bg-bar-snow {
    background-color: rgba(45, 145, 230, 0.5);
}

.bg-bar-heavy-snow {
    background-color: rgba(30, 130, 200, 0.5);
}

.bg-bar-hazy {
    background-color: rgba(180, 90, 80, 0.5);
}

.bg-bar-hail {
    background-color: rgba(30, 60, 90, 0.5);
}
</style>
