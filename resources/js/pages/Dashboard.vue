<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DashboardContent from '@/components/dashboard/DashboardContent.vue';
import type { DashboardLayout } from '@/components/dashboard/layout';
import { dashboard } from '@/routes';
import type { Team } from '@/types';

defineProps<{
    currentTeam?: Team | null;
    /** The page being shown; its layout is null until the default page is customised. */
    dashboardPage: { id: number; name: string; slug: string; is_default: boolean; layout: DashboardLayout | null };
    agenda_cached?: any[];
    agenda?: any[];
    netdata?: any[];
}>();

defineOptions({
    layout: (layoutProps: { currentTeam?: Team | null }) => ({
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: layoutProps.currentTeam
                    ? dashboard(layoutProps.currentTeam.slug)
                    : '/',
            },
        ],
    }),
});
</script>

<template>
    <Head :title="dashboardPage.name" />

    <DashboardContent :dashboard-page="dashboardPage" :agenda="agenda" :agenda_cached="agenda_cached" :netdata="netdata" />
</template>
