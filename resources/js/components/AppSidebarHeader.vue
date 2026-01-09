<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { Separator } from '@/components/ui/separator';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-slate-800/50 bg-background-dark/80 backdrop-blur-md px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-16 z-10 sticky top-0">
        <div class="flex items-center gap-4 flex-1 max-w-2xl">
            <SidebarTrigger class="-ml-1 text-slate-400 hover:text-white" />

            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Separator orientation="vertical" class="mr-2 h-4 bg-slate-700" />
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>

            <!-- Search -->
            <div class="flex-1 max-w-xl hidden md:block ml-2">
                <label class="relative group flex items-center">
                    <div
                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-500 group-focus-within:text-primary transition-colors">
                        <span class="material-symbols-outlined text-[20px]">search</span>
                    </div>
                    <input
                        class="block w-full pl-10 pr-12 py-2 bg-surface-dark/50 border border-slate-700/50 rounded-lg text-sm text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-all font-mono"
                        placeholder="Search infrastructure..." type="text" />
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <kbd
                            class="hidden sm:inline-flex items-center gap-1 px-1.5 py-0.5 border border-slate-700/50 rounded bg-slate-800 text-[10px] font-mono text-slate-400">
                            <span class="text-xs">⌘</span>K
                        </kbd>
                    </div>
                </label>
            </div>
        </div>

        <!-- Pulse Monitors (CPU/RAM Small) -->
        <div class="items-center gap-6 pl-6 hidden md:flex">
            <!-- CPU Pulse -->
            <div class="flex flex-col items-end min-w-[100px]">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">CPU Pulse</span>
                    <span class="text-xs font-mono font-bold text-emerald-400">12%</span>
                </div>
                <div class="w-24 h-6 relative flex items-end gap-[2px]">
                    <!-- Fake sparkline bars -->
                    <div class="w-1 bg-slate-700 h-[20%] rounded-t-sm"></div>
                    <div class="w-1 bg-slate-700 h-[40%] rounded-t-sm"></div>
                    <div class="w-1 bg-emerald-500/50 h-[60%] rounded-t-sm"></div>
                    <div class="w-1 bg-emerald-500 h-[30%] rounded-t-sm"></div>
                    <div class="w-1 bg-slate-700 h-[25%] rounded-t-sm"></div>
                    <div class="w-1 bg-slate-700 h-[45%] rounded-t-sm"></div>
                    <div class="w-1 bg-slate-700 h-[20%] rounded-t-sm"></div>
                    <div class="w-1 bg-emerald-500 h-[70%] rounded-t-sm"></div>
                    <div class="w-1 bg-emerald-500 h-[40%] rounded-t-sm"></div>
                    <div class="w-1 bg-slate-700 h-[30%] rounded-t-sm"></div>
                </div>
            </div>
            <!-- RAM Pulse -->
            <div class="flex flex-col items-end min-w-[100px]">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">RAM Pulse</span>
                    <span class="text-xs font-mono font-bold text-primary">42%</span>
                </div>
                <div class="w-24 h-6 relative flex items-end gap-[2px]">
                    <div class="w-1 bg-primary/30 h-[40%] rounded-t-sm"></div>
                    <div class="w-1 bg-primary/30 h-[45%] rounded-t-sm"></div>
                    <div class="w-1 bg-primary/50 h-[50%] rounded-t-sm"></div>
                    <div class="w-1 bg-primary/50 h-[52%] rounded-t-sm"></div>
                    <div class="w-1 bg-primary h-[55%] rounded-t-sm"></div>
                    <div class="w-1 bg-primary h-[50%] rounded-t-sm"></div>
                    <div class="w-1 bg-primary/50 h-[48%] rounded-t-sm"></div>
                    <div class="w-1 bg-primary/30 h-[45%] rounded-t-sm"></div>
                    <div class="w-1 bg-primary/30 h-[42%] rounded-t-sm"></div>
                    <div class="w-1 bg-primary/30 h-[40%] rounded-t-sm"></div>
                </div>
            </div>
        </div>
    </header>
</template>
