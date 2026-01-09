<script setup lang="ts">
import WidgetTitle from '@/components/dashboard/WidgetTitle.vue';
// Logic will be implemented later
</script>

<template>
  <div class="flex flex-col gap-4">
    <WidgetTitle server="Portainer" title="Docker Events">
      <template #icon>
        <span class="material-symbols-outlined text-indigo-400">history</span>
      </template>
    </WidgetTitle>
    <div
      class="flex max-h-150 flex-1 flex-col rounded-xl border border-gray-200 bg-white shadow-sm dark:border-border-dark dark:bg-slate-950 dark:shadow-none"
    >
      <div
        class="flex items-center justify-between border-b border-gray-200 bg-gray-50 p-4 dark:border-border-dark dark:bg-surface-dark/30"
      >
        <span class="font-mono text-xs tracking-widest text-slate-400 uppercase">Real-time Stream</span>
        <div class="flex gap-1">
          <div class="size-1.5 animate-pulse rounded-full bg-indigo-500"></div>
          <div class="size-1.5 rounded-full bg-indigo-500/50"></div>
          <div class="size-1.5 rounded-full bg-indigo-500/20"></div>
        </div>
      </div>
      <div class="space-y-4 overflow-y-auto p-4 font-mono text-xs">
        <div class="flex gap-3">
          <span class="shrink-0 text-slate-600">14:22:10</span>
          <div class="flex-1">
            <span class="font-bold text-indigo-500 dark:text-indigo-400">Container</span>
            <span class="text-gray-900 dark:text-white"> jellyfin</span>
            <span class="text-green-500 dark:text-green-400"> started</span>
            <p class="mt-0.5 text-[10px] text-slate-500">ID: 8a4c12ef09b2</p>
          </div>
        </div>
        <div class="flex gap-3">
          <span class="shrink-0 text-slate-600">14:21:45</span>
          <div class="flex-1">
            <span class="font-bold text-indigo-500 dark:text-indigo-400">Image</span>
            <span class="text-gray-900 dark:text-white"> postgres:14</span>
            <span class="text-blue-500 dark:text-blue-400"> pulled</span>
            <p class="mt-0.5 text-[10px] text-slate-500">Registry: docker.io</p>
          </div>
        </div>
        <div class="flex gap-3">
          <span class="shrink-0 text-slate-600">14:18:02</span>
          <div class="flex-1">
            <span class="font-bold text-indigo-500 dark:text-indigo-400">Network</span>
            <span class="text-gray-900 dark:text-white"> db_default</span>
            <span class="text-slate-500 dark:text-slate-400"> connected</span>
          </div>
        </div>
        <div class="flex gap-3 opacity-60">
          <span class="shrink-0 text-slate-600">14:05:12</span>
          <div class="flex-1">
            <span class="font-bold text-indigo-500 dark:text-indigo-400">Container</span>
            <span class="text-gray-900 dark:text-white"> radarr</span>
            <span class="text-red-500 dark:text-red-400"> stopped</span>
            <p class="mt-0.5 text-[10px] text-slate-500">Exit Code: 0</p>
          </div>
        </div>
        <div class="flex gap-3 opacity-50">
          <span class="shrink-0 text-slate-600">13:58:22</span>
          <div class="flex-1">
            <span class="font-bold text-indigo-500 dark:text-indigo-400">Volume</span>
            <span class="text-gray-900 dark:text-white"> config_data</span>
            <span class="text-slate-500 dark:text-slate-400"> mounted</span>
          </div>
        </div>
        <div class="flex gap-3 opacity-30">
          <span class="shrink-0 text-slate-600">13:45:00</span>
          <div class="flex-1">
            <span class="font-bold text-indigo-500 dark:text-indigo-400">Stack</span>
            <span class="text-gray-900 dark:text-white"> traefik</span>
            <span class="text-yellow-500 dark:text-yellow-400"> updated</span>
          </div>
        </div>
      </div>
      <div class="mt-auto border-t border-gray-200 p-4 dark:border-border-dark">
        <button
          class="w-full rounded-lg border border-gray-200 py-2 text-center text-xs font-bold text-slate-400 transition-all hover:bg-gray-100 hover:text-gray-900 dark:border-border-dark dark:hover:bg-surface-dark dark:hover:text-white"
        >
          Clear History
        </button>
      </div>
    </div>
  </div>
</template>
