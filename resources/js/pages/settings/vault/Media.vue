<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import SecretInput from '@/components/Settings/SecretInput.vue';
import TestConnectionBtn from '@/components/Settings/TestConnectionBtn.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { sidebarNavItems } from '@/layouts/settings/items';
import routeSettings from '@/routes/settings';
import { NavItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
  settings: {
    type: Object,
    required: true,
  },
});

const form = useForm({
  ...props.settings, // spread media settings
});

const submit = () => {
  form.put(routeSettings.media.update.url(), {
    preserveScroll: true,
  });
};

const navItems: NavItem[] = sidebarNavItems;
</script>

<template>
  <AppLayout :breadcrumbs="[
    { title: 'Settings', href: routeSettings.general.url() },
    { title: 'Media Center', href: '' },
  ]">

    <Head title="Media Settings" />

    <SettingsLayout :nav-items="navItems">
      <div class="space-y-6">
        <HeadingSmall title="Media Center" description="Configure media server connections" />

        <form @submit.prevent="submit" class="space-y-6">
          <!-- Jellyfin & Jellyseerr -->
          <Card class="bg-white border-slate-200 dark:bg-surface-dark/40 dark:border-slate-800 backdrop-blur-sm">
            <CardHeader class="flex flex-row items-center justify-between border-b border-border p-4">
              <div class="flex items-center gap-3">
                <CardTitle class="text-base">Jellyfin</CardTitle>
              </div>
              <TestConnectionBtn service="jellyfin" :payload="form" />
            </CardHeader>
            <CardContent class="grid gap-6 p-6">
              <div class="flex flex-col gap-2">
                <Label>URL</Label>
                <Input v-model="form.jellyfin_url" class="mt-1 block w-full font-mono" />
                <InputError class="mt-2" :message="form.errors.jellyfin_url" />
              </div>
              <div class="flex flex-col gap-2">
                <Label>API Key</Label>
                <SecretInput v-model="form.jellyfin_api_key" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.jellyfin_api_key" />
              </div>
            </CardContent>
          </Card>

          <Card class="bg-white border-slate-200 dark:bg-surface-dark/40 dark:border-slate-800 backdrop-blur-sm">
            <CardHeader class="flex flex-row items-center justify-between border-b border-border p-4">
              <div class="flex items-center gap-3">
                <CardTitle class="text-base">Jellyseerr</CardTitle>
              </div>
              <TestConnectionBtn service="jellyseerr" :payload="form" />
            </CardHeader>
            <CardContent class="grid gap-6 p-6">
              <div class="flex flex-col gap-2">
                <Label>URL</Label>
                <Input v-model="form.jellyseerr_url" class="mt-1 block w-full font-mono" />
                <InputError class="mt-2" :message="form.errors.jellyseerr_url" />
              </div>
              <div class="flex flex-col gap-2">
                <Label>API Key</Label>
                <SecretInput v-model="form.jellyseerr_api_key" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.jellyseerr_api_key" />
              </div>
            </CardContent>
          </Card>

          <!-- Transmission -->
          <Card class="bg-white border-slate-200 dark:bg-surface-dark/40 dark:border-slate-800 backdrop-blur-sm">
            <CardHeader class="flex flex-row items-center justify-between border-b border-border p-4">
              <div class="flex items-center gap-3">
                <CardTitle class="text-base">Transmission</CardTitle>
              </div>
              <TestConnectionBtn service="transmission" :payload="form" />
            </CardHeader>
            <CardContent class="grid grid-cols-1 gap-6 p-6 md:grid-cols-3">
              <div class="flex flex-col gap-2">
                <Label>RPC URL</Label>
                <Input v-model="form.transmission_url" class="mt-1 block w-full font-mono" />
                <InputError class="mt-2" :message="form.errors.transmission_url" />
              </div>
              <div class="flex flex-col gap-2">
                <Label>Username</Label>
                <Input v-model="form.transmission_username" class="mt-1 block w-full font-mono" />
                <InputError class="mt-2" :message="form.errors.transmission_username" />
              </div>
              <div class="flex flex-col gap-2">
                <Label>Password</Label>
                <SecretInput v-model="form.transmission_password" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.transmission_password" />
              </div>
            </CardContent>
          </Card>

          <div class="flex items-center justify-end gap-4">
            <Button type="submit" :disabled="form.processing">Save Changes</Button>

            <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
              leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
              <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">
                Saved.
              </p>
            </Transition>
          </div>
        </form>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
