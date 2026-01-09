<script setup lang="ts">
import { containers } from '@/actions/App/Http/Controllers/Api/PortainerStatusController';
import StatWidget from '@/components/dashboard/StatWidget.vue';
import ContainerStatusChart from '@/components/portainer/widgets/ContainerStatusChart.vue';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuRadioGroup,
  DropdownMenuRadioItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useEventBus, useIntervalFn } from '@vueuse/core';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';

const stats = ref<{
  total: number;
  running: number;
  stopped: number;
  other: number;
}>({ total: 0, running: 0, stopped: 0, other: 0 });

const loading = ref(true);
const error = ref<string | null>(null);

// Default refresh rate: 10s in Dev, 1min in Prod
const isProd = import.meta.env.PROD;
const refreshRate = ref(isProd ? '60000' : '10000');

const fetchStats = async () => {
  try {
    const response = await axios.request(containers());
    stats.value = response.data;
    error.value = null;
  } catch (e: any) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
};

const intervalDelay = computed(() => parseInt(refreshRate.value));

const { resume } = useIntervalFn(
  () => {
    fetchStats();
  },
  intervalDelay,
  { immediate: false },
);

onMounted(() => {
  fetchStats();
  resume();
});

const bus = useEventBus<string>('portainer-stats-update');
bus.on(() => fetchStats());

const runningPercentage = computed(() => {
  if (stats.value.total === 0) return 0;
  return Math.round((stats.value.running / stats.value.total) * 100);
});

const statusValue = computed(() => {
  if (loading.value) return '...';
  return `${stats.value.running} Active`;
});
</script>

<template>
  <StatWidget title="Containers" icon="view_in_ar" icon-color="text-indigo-500" :value="statusValue"
    :subtitle="`${runningPercentage}% Running`" subtitleClass="font-mono">
    <template #actions>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <button class="text-slate-400 transition-colors outline-none hover:text-primary"
            :class="{ 'animate-spin': loading }">
            <span class="material-symbols-outlined text-lg">more_vert</span>
          </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuLabel>Refresh Rate</DropdownMenuLabel>
          <DropdownMenuRadioGroup v-model="refreshRate">
            <DropdownMenuRadioItem value="1000">1s (Rapid)</DropdownMenuRadioItem>
            <DropdownMenuRadioItem value="10000">10s (Normal)</DropdownMenuRadioItem>
            <DropdownMenuRadioItem value="60000">1m (Slow)</DropdownMenuRadioItem>
          </DropdownMenuRadioGroup>
          <DropdownMenuSeparator />
          <DropdownMenuItem @click="fetchStats" class="cursor-pointer">
            <span class="material-symbols-outlined mr-2 text-base">refresh</span>
            Refresh Now
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </template>
    <template #chart>
      <div class="flex h-full items-end justify-end p-3.5 pe-10">
        <ContainerStatusChart :data="stats" />
      </div>
    </template>
  </StatWidget>
</template>
