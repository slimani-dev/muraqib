<script setup lang="ts">
import WidgetTitle from '@/components/dashboard/WidgetTitle.vue';
// Logic will be implemented later
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between mb-2">
            <WidgetTitle server="Portainer" title="Active Stacks">
                <template #icon>
                    <span class="material-symbols-outlined text-primary">view_quilt</span>
                </template>
            </WidgetTitle>
            <button
                class="bg-primary hover:bg-indigo-500 text-white text-sm font-bold px-4 py-2 rounded-lg transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-[18px]">add</span>
                New Stack
            </button>
        </div>
        <div class="grid grid-cols-1 gap-3">
            <!-- Stack Item 1 -->
            <div
                class="bg-white dark:bg-surface-dark border border-gray-200 dark:border-border-dark rounded-xl overflow-hidden group hover:border-primary/50 transition-all">
                <div class="h-1 w-full bg-green-500"></div>
                <div class="p-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div
                            class="size-12 rounded-lg bg-slate-100 dark:bg-[#2d333b] border border-gray-200 dark:border-border-dark flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-3xl">terminal</span>
                        </div>
                        <div>
                            <h4 class="text-gray-900 dark:text-white font-bold text-lg leading-tight">homelab-media
                            </h4>
                            <div class="flex items-center gap-3 mt-1">
                                <span
                                    class="text-xs font-mono text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-950 px-2 py-0.5 rounded border border-gray-200 dark:border-border-dark">5
                                    Containers</span>
                                <span
                                    class="text-xs font-mono text-green-400 bg-green-500/10 px-2 py-0.5 rounded border border-green-500/20">Active</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="relative group">
                            <button
                                class="bg-gray-100 dark:bg-surface-highlight hover:bg-primary/20 hover:text-primary text-slate-500 dark:text-slate-300 px-3 py-1.5 rounded-lg text-xs font-bold transition-all border border-transparent hover:border-primary/30 flex items-center gap-2">
                                <span class="material-symbols-outlined text-[16px]">system_update_alt</span>
                                Update Stack
                            </button>
                            <span class="absolute -top-1 -right-1 flex size-2.5">
                                <span
                                    class="animate-ping absolute inline-flex h-full w-full rounded-full bg-yellow-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full size-2.5 bg-yellow-500"></span>
                            </span>
                        </div>
                        <button
                            class="bg-gray-100 dark:bg-surface-highlight hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-500 dark:text-slate-300 p-1.5 rounded-lg transition-all"
                            title="Restart">
                            <span class="material-symbols-outlined text-[18px]">refresh</span>
                        </button>
                        <button
                            class="bg-gray-100 dark:bg-surface-highlight hover:bg-red-500/20 hover:text-red-400 text-slate-500 dark:text-slate-300 p-1.5 rounded-lg transition-all"
                            title="Stop">
                            <span class="material-symbols-outlined text-[18px]">stop</span>
                        </button>
                        <button
                            class="bg-slate-100 dark:bg-slate-950 hover:bg-slate-200 dark:hover:bg-slate-900 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white px-3 py-1.5 rounded-lg text-xs font-mono border border-gray-200 dark:border-border-dark flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px]">edit_note</span>
                            YAML
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stack Item 2 -->
            <div
                class="bg-white dark:bg-surface-dark border border-gray-200 dark:border-border-dark rounded-xl overflow-hidden group hover:border-primary/50 transition-all">
                <div class="h-1 w-full bg-green-500"></div>
                <div class="p-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div
                            class="size-12 rounded-lg bg-[#2d333b] border border-border-dark flex items-center justify-center text-primary">
                            <span class="material-symbols-outlined text-3xl">database</span>
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-lg leading-tight">database-cluster</h4>
                            <div class="flex items-center gap-3 mt-1">
                                <span
                                    class="text-xs font-mono text-slate-400 bg-slate-950 px-2 py-0.5 rounded border border-border-dark">3
                                    Containers</span>
                                <span
                                    class="text-xs font-mono text-green-400 bg-green-500/10 px-2 py-0.5 rounded border border-green-500/20">Active</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            class="bg-surface-highlight hover:bg-primary/20 hover:text-primary text-slate-300 px-3 py-1.5 rounded-lg text-xs font-bold transition-all border border-transparent hover:border-primary/30 flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px]">system_update_alt</span>
                            Update Stack
                        </button>
                        <button
                            class="bg-gray-100 dark:bg-surface-highlight hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-500 dark:text-slate-300 p-1.5 rounded-lg transition-all"
                            title="Restart">
                            <span class="material-symbols-outlined text-[18px]">refresh</span>
                        </button>
                        <button
                            class="bg-gray-100 dark:bg-surface-highlight hover:bg-red-500/20 hover:text-red-400 text-slate-500 dark:text-slate-300 p-1.5 rounded-lg transition-all"
                            title="Stop">
                            <span class="material-symbols-outlined text-[18px]">stop</span>
                        </button>
                        <button
                            class="bg-slate-100 dark:bg-slate-950 hover:bg-slate-200 dark:hover:bg-slate-900 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white px-3 py-1.5 rounded-lg text-xs font-mono border border-gray-200 dark:border-border-dark flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px]">edit_note</span>
                            YAML
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stack Item 3 -->
            <div
                class="bg-white dark:bg-surface-dark border border-gray-200 dark:border-border-dark rounded-xl overflow-hidden group hover:border-red-500/50 transition-all opacity-80">
                <div class="h-1 w-full bg-red-500"></div>
                <div class="p-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div
                            class="size-12 rounded-lg bg-slate-100 dark:bg-[#2d333b] border border-gray-200 dark:border-border-dark flex items-center justify-center text-slate-500">
                            <span class="material-symbols-outlined text-3xl">monitoring</span>
                        </div>
                        <div>
                            <h4 class="text-slate-500 dark:text-slate-400 font-bold text-lg leading-tight">
                                monitoring-grafana</h4>
                            <div class="flex items-center gap-3 mt-1">
                                <span
                                    class="text-xs font-mono text-slate-500 bg-slate-100 dark:bg-slate-950 px-2 py-0.5 rounded border border-gray-200 dark:border-border-dark">8
                                    Containers</span>
                                <span
                                    class="text-xs font-mono text-red-500 dark:text-red-400 bg-red-50 dark:bg-red-500/10 px-2 py-0.5 rounded border border-red-200 dark:border-red-500/20 uppercase tracking-tighter font-bold">Stopped</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            class="bg-green-500 hover:bg-green-600 text-white px-4 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-2 shadow-lg shadow-green-500/20">
                            <span class="material-symbols-outlined text-[16px]">play_arrow</span>
                            Start Stack
                        </button>
                        <button
                            class="bg-slate-100 dark:bg-slate-950 hover:bg-slate-200 dark:hover:bg-slate-900 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white px-3 py-1.5 rounded-lg text-xs font-mono border border-gray-200 dark:border-border-dark flex items-center gap-2">
                            <span class="material-symbols-outlined text-[16px]">edit_note</span>
                            YAML
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
