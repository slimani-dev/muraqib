<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as settingsIndex } from '@/routes/settings';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';

// Use strings for icons to support specific Material Symbols
const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: 'dashboard',
    },
    {
        title: 'App Store',
        href: '/apps', // Placeholder
        icon: 'apps',
    },
    {
        title: 'Infrastructure',
        href: '/infrastructure', // Placeholder
        icon: 'dns',
    },
    {
        title: 'Media',
        href: '/media', // Placeholder
        icon: 'movie',
    },
    {
        title: 'Dev',
        href: '/dev', // Placeholder
        icon: 'terminal',
    },
    {
        title: 'Settings',
        href: settingsIndex(),
        icon: 'settings',
    },
];

</script>

<template>
    <!-- Use bg-sidebar-dark and border styles from Dashboard -->
    <Sidebar variant="sidebar" class="border-r border-slate-800/50 bg-sidebar-dark" collapsible="icon">
        <SidebarHeader
            class="h-16 flex items-center justify-center px-6 border-b border-slate-800/50 group-data-[collapsible=icon]:px-2">
            <div class="flex items-center gap-3 w-full group-data-[collapsible=icon]:justify-center">
                <div class="w-8 h-8 rounded-lg bg-primary/20 flex items-center justify-center text-primary shrink-0">
                    <span class="material-symbols-outlined text-xl">grid_view</span>
                </div>
                <div class="flex flex-col group-data-[collapsible=icon]:hidden transition-all">
                    <h1 class="text-white text-sm font-bold tracking-wide">Muraqib</h1>
                    <p class="text-slate-500 text-xs font-mono">v2.4.0</p>
                </div>
            </div>
        </SidebarHeader>

        <SidebarContent class="py-4 px-3 bg-sidebar-dark">
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter class="p-4 border-t border-slate-800/50 bg-sidebar-dark group-data-[collapsible=icon]:p-2">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
