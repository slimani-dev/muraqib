<script setup lang="ts">
import { stacks } from '@/actions/App/Http/Controllers/Api/PortainerStatusController';
import StatWidget from '@/components/dashboard/StatWidget.vue';
import StacksChart from '@/components/portainer/widgets/StacksChart.vue';
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
import { useIntervalFn } from '@vueuse/core';
import axios from 'axios';
import { computed, onMounted, ref } from 'vue';

const stats = ref<{
  total: number;
  active: number;
  inactive: number;
}>({ total: 0, active: 0, inactive: 0 });

const loading = ref(true);
const error = ref<string | null>(null);

// Default refresh rate: 10s in Dev, 1min in Prod
const isProd = import.meta.env.PROD;
const refreshRate = ref(isProd ? '60000' : '10000');

const fetchStats = async () => {
  try {
    const response = await axios.request(stacks());
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

const activePercentage = computed(() => {
  if (stats.value.total === 0) return 0;
  return Math.round((stats.value.active / stats.value.total) * 100);
});

const statusValue = computed(() => {
  if (loading.value) return '...';
  return `${stats.value.active} Active`;
});
</script>

<template>
  <StatWidget title="Stacks" icon="layers" icon-color="text-primary/80 dark:text-primary" :value="statusValue"
    :subtitle="`${activePercentage}% Active`">
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
        <StacksChart :data="stats" />
      </div>
    </template>
  </StatWidget>
</template>
