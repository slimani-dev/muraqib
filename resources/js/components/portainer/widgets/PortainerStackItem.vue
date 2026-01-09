<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { computed } from 'vue';

const props = defineProps<{
  stack: any;
  processingAction: 'start' | 'stop' | 'restart' | null;
}>();

const emit = defineEmits<{
  (e: 'start', id: number): void;
  (e: 'stop', id: number): void;
  (e: 'restart', id: number): void;
  (e: 'update', id: number): void;
}>();

const isRunning = computed(() => props.stack.Status === 1);
const isStopped = computed(() => props.stack.Status === 2);
const totalContainers = computed(() => props.stack.containers_count ?? 0);
const runningContainers = computed(() => props.stack.running_count ?? 0);
const stoppedContainers = computed(() => props.stack.stopped_count ?? 0);

const isActionInProgress = computed(() => props.processingAction !== null);
</script>

<template>
  <div
    class="group flex flex-row overflow-hidden rounded-xl border border-gray-200 bg-white transition-all hover:border-primary/50 dark:border-border-dark dark:bg-surface-dark"
    :class="{ 'opacity-80': isStopped && runningContainers === 0 }">
    <!-- Status Bar -->
    <div class="h-full w-1" :class="isRunning ? 'bg-green-500' : 'bg-red-500'"></div>

    <div class="flex grow flex-col justify-between gap-4 p-4 md:flex-row md:items-center">
      <div class="flex items-center gap-4">
        <div
          class="flex size-12 items-center justify-center rounded-lg border border-gray-200 bg-slate-100 text-primary dark:border-border-dark dark:bg-[#2d333b]"
          :class="{ 'text-slate-500': isStopped, 'bg-transparent border-0': stack.icon }">
          <img v-if="stack.icon" :src="stack.icon" :alt="stack.Name" class="size-10 object-contain" />
          <span v-else class="material-symbols-outlined text-3xl">{{ isRunning ? 'terminal' : 'dns' }}</span>
        </div>
        <div>
          <h4 class="text-lg leading-tight font-bold text-gray-900 dark:text-white"
            :class="{ 'text-slate-500 dark:text-slate-400': isStopped }">
            {{ stack.Name }}
          </h4>
          <div class="mt-1 flex flex-wrap items-center gap-3">
            <span
              class="rounded border border-gray-200 bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-500 dark:border-border-dark dark:bg-slate-950 dark:text-slate-400">
              {{ totalContainers }} Containers
            </span>
            <span v-if="runningContainers > 0"
              class="rounded border border-green-500/20 bg-green-500/10 px-2 py-0.5 font-mono text-xs text-green-400">
              {{ runningContainers }} Active
            </span>
            <span v-if="stoppedContainers > 0"
              class="rounded border border-red-200 bg-red-50 px-2 py-0.5 font-mono text-xs font-bold text-red-500 dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
              {{ stoppedContainers }} Stopped
            </span>
            <span v-if="isStopped && runningContainers === 0"
              class="rounded border border-red-200 bg-red-50 px-2 py-0.5 font-mono text-xs font-bold tracking-tighter text-red-500 uppercase dark:border-red-500/20 dark:bg-red-500/10 dark:text-red-400">
              Stopped
            </span>
          </div>
        </div>
      </div>

      <div class="md:items- flex h-full flex-col items-start justify-end gap-2 md:flex-row">
        <!-- Actions -->

        <Button v-if="isStopped" @click="emit('start', stack.Id)" :disabled="isActionInProgress" size="sm"
          class="gap-2 bg-green-500 text-white shadow-lg shadow-green-500/20 hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-500">
          <span v-if="processingAction === 'start'"
            class="material-symbols-outlined animate-spin text-[16px]">refresh</span>
          <span v-else class="material-symbols-outlined text-[16px]">play_arrow</span>
          {{ processingAction === 'start' ? 'Starting...' : 'Start' }}
        </Button>

        <Button v-if="isRunning" @click="emit('restart', stack.Id)" :disabled="isActionInProgress" variant="outline"
          size="icon" title="Restart Stack" class="border-0">
          <span v-if="processingAction === 'restart'"
            class="material-symbols-outlined animate-spin text-[16px]">refresh</span>
          <span v-else class="material-symbols-outlined text-[16px]">restart_alt</span>
        </Button>

        <Button v-if="isRunning" @click="emit('stop', stack.Id)" :disabled="isActionInProgress" variant="destructive"
          size="icon" title="Stop">
          <span v-if="processingAction === 'stop'"
            class="material-symbols-outlined animate-spin text-[16px]">refresh</span>
          <span v-else class="material-symbols-outlined text-[16px]">stop_circle</span>
        </Button>

        <!-- YAML Button -->
        <!--  Keep this commented
        <Button variant="outline" size="sm" class="gap-2 font-mono text-xs text-slate-500 dark:text-slate-400">
          <span class="material-symbols-outlined text-[16px]">edit_note</span>
          YAML
        </Button>
        -->
      </div>
    </div>
  </div>
</template>
