<script setup lang="ts">
import { check } from '@/actions/App/Http/Controllers/Api/PortainerStatusController';
import StatWidget from '@/components/dashboard/StatWidget.vue';
import LatencyChart from '@/components/portainer/widgets/LatencyChart.vue';
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

const status = ref<'checking' | 'connected' | 'error'>('checking');
const latency = ref<number | null>(null);
const errorMessage = ref<string | null>(null);
const latencyHistory = ref<{ date: number; latency: number }[]>([]);

// Default refresh rate: 10s in Dev, 1min in Prod
const isProd = import.meta.env.PROD;
const refreshRate = ref(isProd ? '60000' : '10000');

const checkStatus = async () => {
  try {
    const response = await axios.request(check());
    status.value = 'connected';
    const newLatency = response.data.latency_ms;
    latency.value = newLatency;
    errorMessage.value = null;

    // Update history
    latencyHistory.value.push({ date: Date.now(), latency: newLatency });
    if (latencyHistory.value.length > 30) {
      latencyHistory.value.shift();
    }
  } catch (error: any) {
    status.value = 'error';
    latency.value = null;
    errorMessage.value = error.response?.data?.message || error.message;
    // Optionally push 0 or null to indicate gap, or just skip
  }
};

const intervalDelay = computed(() => parseInt(refreshRate.value));

const { resume } = useIntervalFn(
  () => {
    checkStatus();
  },
  intervalDelay,
  { immediate: false },
);

// Restart interval when refresh rate changes (handled automatically by useIntervalFn with ref/computed, but let's be safe or just rely on it)
// actually useIntervalFn with ref restarts automatically.
// But I had a watcher before. Removing watcher if useIntervalFn handles it.
// Documentation: "If the delay is a ref, the interval will automatically restart when the delay changes."
// So I can remove the watcher.

onMounted(() => {
  checkStatus();
  resume();
});
</script>

<template>
  <StatWidget
    title="API Connection"
    icon="hub"
    :icon-color="status === 'error' ? 'text-red-500' : 'text-green-500'"
    value="Status"
    :subtitle="latency ? `${latency} ms` : undefined"
    :variant="status === 'error' ? 'warning' : 'default'"
    class="group/stat"
  >
    <template #actions>
      <DropdownMenu>
        <DropdownMenuTrigger as-child>
          <button
            class="text-slate-400 transition-colors outline-none hover:text-primary"
            :class="{ 'animate-spin': status === 'checking' }"
          >
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
          <DropdownMenuItem @click="checkStatus" class="cursor-pointer">
            <span class="material-symbols-outlined mr-2 text-base">refresh</span>
            Refresh Now
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </template>
    <template #content>
      <!-- Connected State -->
      <div
        v-if="status === 'connected'"
        class="relative z-10 flex h-10 w-fit items-center gap-2 rounded-full border border-green-500/20 bg-green-500/10 px-4 py-1.5 backdrop-blur-sm"
      >
        <div class="size-2 animate-pulse rounded-full bg-green-500"></div>
        <div class="flex flex-col">
          <span class="font-mono text-xs leading-none font-bold text-green-500 uppercase">Healthy / Connected</span>
        </div>
      </div>

      <!-- Error State -->
      <div
        v-else-if="status === 'error'"
        class="relative z-10 flex h-10 w-fit items-center gap-2 rounded-full border border-red-500/20 bg-red-500/10 px-4 py-1.5 backdrop-blur-sm"
        :title="errorMessage || 'Connection Error'"
      >
        <div class="size-2 rounded-full bg-red-500"></div>
        <span class="font-mono text-xs font-bold text-red-500 uppercase">Disconnected</span>
      </div>

      <!-- Checking State -->
      <div
        v-else
        class="relative z-10 flex h-10 w-fit items-center gap-2 rounded-full border border-slate-200 bg-slate-100 px-4 py-1.5 dark:border-slate-700 dark:bg-slate-800"
      >
        <span class="material-symbols-outlined animate-spin text-sm text-slate-500">sync</span>
        <span class="font-mono text-xs font-bold text-slate-500 uppercase dark:text-slate-400">Checking...</span>
      </div>
    </template>
    <template #chart>
      <LatencyChart v-if="latencyHistory.length > 1" :data="latencyHistory" :color="latency ? '#4ade80' : '#f12c29'" />
    </template>
  </StatWidget>
</template>
