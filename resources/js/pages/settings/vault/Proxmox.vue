<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { sidebarNavItems } from '@/layouts/settings/items';
import routeSettings from '@/routes/settings';
import { NavItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import ProxmoxCard from '@/components/Settings/Infrastructure/ProxmoxCard.vue';

const props = defineProps({
  settings: {
    type: Object,
    required: true,
  },
});

const navItems: NavItem[] = sidebarNavItems;
</script>

<template>
  <AppLayout :breadcrumbs="[
    { title: 'Settings', href: routeSettings.general.url() },
    { title: 'Proxmox', href: '' },
  ]">

    <Head title="Proxmox Settings" />

    <SettingsLayout :nav-items="navItems">
      <div class="space-y-6">
        <HeadingSmall title="Proxmox" description="Manage virtualization nodes" />

        <div class="space-y-6">
          <ProxmoxCard :settings="props.settings" />
        </div>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
