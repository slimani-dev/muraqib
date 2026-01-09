<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Input } from '@/components/ui/input';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';
import { Search } from 'lucide-vue-next';
import { ref } from 'vue';

const props = withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const searchValue = ref('');
</script>

<template>
    <header
        class="h-16 shrink-0 border-b border-sidebar-border bg-sidebar/80 backdrop-blur-md transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-16 z-10 sticky top-0">
        <div class="relative flex items-center gap-2 h-full px-6">
            <!-- Left side: Sidebar trigger and breadcrumbs -->
            <div class="flex items-center gap-4 flex-1 min-w-0">
                <SidebarTrigger class="-ml-1 text-muted-foreground hover:text-sidebar-foreground" />

                <template v-if="props.breadcrumbs && props.breadcrumbs.length > 0">
                    <Breadcrumbs :breadcrumbs="props.breadcrumbs" />
                </template>
            </div>

            <!-- Center: Search bar (absolutely positioned) -->
            <div class="absolute left-1/2 -translate-x-1/2 hidden md:block w-full max-w-md">
                <div class="relative group">
                    <div
                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-muted-foreground group-focus-within:text-sidebar-foreground transition-colors">
                        <Search class="size-4" />
                    </div>
                    <Input v-model="searchValue" type="text" placeholder="Search infrastructure..."
                        class="pl-10 pr-16 bg-sidebar-accent/50 border-sidebar-border text-sidebar-foreground placeholder-muted-foreground focus-visible:border-sidebar-foreground focus-visible:ring-sidebar-foreground/50" />
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <kbd
                            class="hidden sm:inline-flex items-center gap-1 px-1.5 py-0.5 border border-sidebar-border rounded bg-sidebar-accent text-[10px] font-mono text-muted-foreground">
                            <span class="text-xs">⌘</span>K
                        </kbd>
                    </div>
                </div>
            </div>

            <!-- Right side: Pulse Monitors (CPU/RAM Small) -->
            <div class="flex items-center gap-6 flex-1 min-w-0 justify-end">
                <div class="items-center gap-6 hidden xl:flex">
                    <!-- CPU Pulse -->
                    <div class="flex flex-col items-start min-w-25">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] font-medium text-muted-foreground uppercase tracking-wider">CPU
                                Pulse</span>
                            <span class="text-xs font-mono font-bold text-emerald-400">12%</span>
                        </div>
                        <div class="w-24 h-6 relative flex items-end gap-0.5">
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
                    <div class="flex flex-col items-start min-w-25">
                        <div class="flex items-center  gap-2 mb-1">
                            <span class="text-[10px] font-medium text-muted-foreground uppercase tracking-wider">RAM
                                Pulse</span>
                            <span class="text-xs font-mono font-bold text-primary">42%</span>
                        </div>
                        <div class="w-24 h-6 relative flex items-end gap-0.5">
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
            </div>
        </div>
    </header>
</template>
