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
            class="bg-white dark:bg-slate-950 border border-gray-200 dark:border-border-dark rounded-xl flex flex-col flex-1 max-h-[600px] shadow-sm dark:shadow-none">
            <div
                class="p-4 border-b border-gray-200 dark:border-border-dark flex items-center justify-between bg-gray-50 dark:bg-surface-dark/30">
                <span class="text-xs font-mono text-slate-400 uppercase tracking-widest">Real-time Stream</span>
                <div class="flex gap-1">
                    <div class="size-1.5 bg-indigo-500 rounded-full animate-pulse"></div>
                    <div class="size-1.5 bg-indigo-500/50 rounded-full"></div>
                    <div class="size-1.5 bg-indigo-500/20 rounded-full"></div>
                </div>
            </div>
            <div class="p-4 overflow-y-auto font-mono text-xs space-y-4">
                <div class="flex gap-3">
                    <span class="text-slate-600 shrink-0">14:22:10</span>
                    <div class="flex-1">
                        <span class="text-indigo-500 dark:text-indigo-400 font-bold">Container</span>
                        <span class="text-gray-900 dark:text-white"> jellyfin</span>
                        <span class="text-green-500 dark:text-green-400"> started</span>
                        <p class="text-[10px] text-slate-500 mt-0.5">ID: 8a4c12ef09b2</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <span class="text-slate-600 shrink-0">14:21:45</span>
                    <div class="flex-1">
                        <span class="text-indigo-500 dark:text-indigo-400 font-bold">Image</span>
                        <span class="text-gray-900 dark:text-white"> postgres:14</span>
                        <span class="text-blue-500 dark:text-blue-400"> pulled</span>
                        <p class="text-[10px] text-slate-500 mt-0.5">Registry: docker.io</p>
                    </div>
                </div>
                <div class="flex gap-3">
                    <span class="text-slate-600 shrink-0">14:18:02</span>
                    <div class="flex-1">
                        <span class="text-indigo-500 dark:text-indigo-400 font-bold">Network</span>
                        <span class="text-gray-900 dark:text-white"> db_default</span>
                        <span class="text-slate-500 dark:text-slate-400"> connected</span>
                    </div>
                </div>
                <div class="flex gap-3 opacity-60">
                    <span class="text-slate-600 shrink-0">14:05:12</span>
                    <div class="flex-1">
                        <span class="text-indigo-500 dark:text-indigo-400 font-bold">Container</span>
                        <span class="text-gray-900 dark:text-white"> radarr</span>
                        <span class="text-red-500 dark:text-red-400"> stopped</span>
                        <p class="text-[10px] text-slate-500 mt-0.5">Exit Code: 0</p>
                    </div>
                </div>
                <div class="flex gap-3 opacity-50">
                    <span class="text-slate-600 shrink-0">13:58:22</span>
                    <div class="flex-1">
                        <span class="text-indigo-500 dark:text-indigo-400 font-bold">Volume</span>
                        <span class="text-gray-900 dark:text-white"> config_data</span>
                        <span class="text-slate-500 dark:text-slate-400"> mounted</span>
                    </div>
                </div>
                <div class="flex gap-3 opacity-30">
                    <span class="text-slate-600 shrink-0">13:45:00</span>
                    <div class="flex-1">
                        <span class="text-indigo-500 dark:text-indigo-400 font-bold">Stack</span>
                        <span class="text-gray-900 dark:text-white"> traefik</span>
                        <span class="text-yellow-500 dark:text-yellow-400"> updated</span>
                    </div>
                </div>
            </div>
            <div class="p-4 border-t border-gray-200 dark:border-border-dark mt-auto">
                <button
                    class="w-full text-center py-2 text-xs font-bold text-slate-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-surface-dark transition-all rounded-lg border border-gray-200 dark:border-border-dark">
                    Clear History
                </button>
            </div>
        </div>
    </div>
</template>
