<script setup lang="ts">
import { index, restart, start, stop } from '@/actions/App/Http/Controllers/Api/PortainerStacksController';
import WidgetTitle from '@/components/dashboard/WidgetTitle.vue';
import { useEventBus, useIntervalFn } from '@vueuse/core';
import axios from 'axios';
import { onMounted, ref } from 'vue';
import PortainerStackItem from './PortainerStackItem.vue';

const stacks = ref<any[]>([]);
const loading = ref(true);
const loadingAction = ref<{ id: number; action: 'start' | 'stop' | 'restart' } | null>(null);

const bus = useEventBus<string>('portainer-stats-update');

const fetchStacks = async () => {
  try {
    const response = await axios.request(index());
    stacks.value = response.data;
  } catch (error) {
    console.error('Failed to fetch stacks', error);
  } finally {
    loading.value = false;
  }
};

const handleStart = async (id: number) => {
  loadingAction.value = { id, action: 'start' };
  try {
    await axios.request(start(id));
    await fetchStacks();
    bus.emit('update');
  } catch (error) {
    console.error('Failed to start stack', error);
  } finally {
    loadingAction.value = null;
  }
};

const handleStop = async (id: number) => {
  loadingAction.value = { id, action: 'stop' };
  try {
    await axios.request(stop(id));
    await fetchStacks();
    bus.emit('update');
  } catch (error) {
    console.error('Failed to stop stack', error);
  } finally {
    loadingAction.value = null;
  }
};

const handleRestart = async (id: number) => {
  loadingAction.value = { id, action: 'restart' };
  try {
    await axios.request(restart(id));
    await fetchStacks();
    bus.emit('update');
  } catch (error) {
    console.error('Failed to restart stack', error);
  } finally {
    loadingAction.value = null;
  }
};

// Refresh every 10 seconds
useIntervalFn(fetchStacks, 10000);

onMounted(fetchStacks);
</script>

<template>
  <div class="flex flex-col gap-4">
    <div class="mb-2 flex items-center justify-between">
      <WidgetTitle server="Portainer" title="Stacks">
        <template #icon>
          <span class="material-symbols-outlined text-primary">view_quilt</span>
        </template>
      </WidgetTitle>
      <button
        class="flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-indigo-500"
      >
        <span class="material-symbols-outlined text-[18px]">add</span>
        New Stack
      </button>
    </div>

    <div v-if="loading && stacks.length === 0" class="py-8 text-center text-slate-500">Loading stacks...</div>

    <div v-else class="grid grid-cols-1 gap-2 md:grid-cols-2">
      <PortainerStackItem
        v-for="stack in stacks"
        :key="stack.Id"
        :stack="stack"
        :processing-action="loadingAction?.id === stack.Id ? (loadingAction?.action ?? null) : null"
        @start="handleStart"
        @stop="handleStop"
        @restart="handleRestart"
      />
    </div>
  </div>
</template>
