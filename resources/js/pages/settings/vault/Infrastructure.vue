<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { sidebarNavItems } from '@/layouts/settings/items';
import routeSettings from '@/routes/settings';
import { NavItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PortainerCard from '@/components/Settings/Infrastructure/PortainerCard.vue';
import CloudflareCard from '@/components/Settings/Infrastructure/CloudflareCard.vue';
import ProxmoxCard from '@/components/Settings/Infrastructure/ProxmoxCard.vue';

const props = defineProps({
  settings: {
    type: Object,
    required: true,
  },
});

// We no longer need a global form or processing state here
// Each card manages its own form

const navItems: NavItem[] = sidebarNavItems;
</script>

<template>
  <AppLayout :breadcrumbs="[
    { title: 'Settings', href: routeSettings.general.url() },
    { title: 'Infrastructure', href: '' },
  ]">

    <Head title="Infrastructure Settings" />

    <SettingsLayout :nav-items="navItems">
      <div class="space-y-6">
        <HeadingSmall title="Infrastructure (The Vault)" description="Manage external service connections" />

        <div class="space-y-6">
          <PortainerCard :settings="props.settings" />
          <CloudflareCard :settings="props.settings" />
          <ProxmoxCard :settings="props.settings" />
        </div>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
