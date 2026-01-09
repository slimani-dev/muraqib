<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import SecretInput from '@/components/Settings/SecretInput.vue';
import TestConnectionBtn from '@/components/Settings/TestConnectionBtn.vue';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
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
  ...props.settings, // spread infrastructure settings
});

const navItems: NavItem[] = sidebarNavItems;

const submit = () => {
  form.put(routeSettings.infrastructure.update.url(), {
    preserveScroll: true,
  });
};
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

        <form @submit.prevent="submit" class="space-y-6">
          <!-- Portainer Card -->
          <Card class="border-slate-200 bg-white backdrop-blur-sm dark:border-slate-800 dark:bg-surface-dark/40">
            <CardHeader class="flex flex-row items-center justify-between border-b border-border p-4">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#0092c2]/10 text-[#0092c2]">
                  <span class="material-symbols-outlined">dock</span>
                </div>
                <div>
                  <CardTitle class="text-base">Portainer</CardTitle>
                  <CardDescription>Docker container orchestration.</CardDescription>
                </div>
              </div>
              <TestConnectionBtn service="portainer" :payload="form" />
            </CardHeader>
            <CardContent class="grid grid-cols-1 gap-6 p-6">
              <div class="flex flex-col gap-2">
                <Label class="text-xs font-medium text-muted-foreground uppercase">URL</Label>
                <Input type="text" v-model="form.portainer_url" class="mt-1 block w-full font-mono" />
                <InputError class="mt-2" :message="form.errors.portainer_url" />
              </div>
              <div class="relative flex flex-col gap-2">
                <Label class="text-xs font-medium text-muted-foreground uppercase">Access Token</Label>
                <SecretInput id="portainer_api_key" v-model="form.portainer_api_key" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.portainer_api_key" />
              </div>
            </CardContent>
          </Card>

          <!-- Cloudflare Card -->
          <Card class="border-slate-200 bg-white backdrop-blur-sm dark:border-slate-800 dark:bg-surface-dark/40">
            <CardHeader class="flex flex-row items-center justify-between border-b border-border p-4">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#F38020]/10 text-[#F38020]">
                  <span class="material-symbols-outlined">cloud</span>
                </div>
                <div>
                  <CardTitle class="text-base">Cloudflare DNS</CardTitle>
                  <CardDescription>Managing DNS records for automatic SSL.</CardDescription>
                </div>
              </div>
              <TestConnectionBtn service="cloudflare" :payload="form" />
            </CardHeader>
            <CardContent class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-2">
              <div class="flex flex-col gap-2">
                <Label class="text-xs font-medium text-muted-foreground uppercase">Email</Label>
                <Input type="email" v-model="form.cloudflare_email" class="mt-1 block w-full font-mono" />
                <InputError class="mt-2" :message="form.errors.cloudflare_email" />
              </div>
              <div class="flex flex-col gap-2">
                <Label class="text-xs font-medium text-muted-foreground uppercase">Account ID</Label>
                <Input type="text" v-model="form.cloudflare_account_id" class="mt-1 block w-full font-mono" />
                <InputError class="mt-2" :message="form.errors.cloudflare_account_id" />
              </div>
              <div class="relative flex flex-col gap-2 lg:col-span-2">
                <Label class="text-xs font-medium text-muted-foreground uppercase">API Token</Label>
                <SecretInput id="cloudflare_api_token" v-model="form.cloudflare_api_token" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.cloudflare_api_token" />
              </div>
            </CardContent>
          </Card>

          <!-- Proxmox Card -->
          <Card class="border-slate-200 bg-white backdrop-blur-sm dark:border-slate-800 dark:bg-surface-dark/40">
            <CardHeader class="flex flex-row items-center justify-between border-b border-border p-4">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#E57000]/10 text-[#E57000]">
                  <span class="material-symbols-outlined">dataset</span>
                </div>
                <div>
                  <CardTitle class="text-base">Proxmox VE</CardTitle>
                  <CardDescription>Hypervisor cluster management.</CardDescription>
                </div>
              </div>
              <TestConnectionBtn service="proxmox" :payload="form" />
            </CardHeader>
            <CardContent class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-3">
              <div class="flex flex-col gap-2">
                <Label class="text-xs font-medium text-muted-foreground uppercase">Host URL</Label>
                <Input type="text" v-model="form.proxmox_url" class="mt-1 block w-full font-mono" />
                <InputError class="mt-2" :message="form.errors.proxmox_url" />
              </div>
              <div class="flex flex-col gap-2">
                <Label class="text-xs font-medium text-muted-foreground uppercase">User (user@realm)</Label>
                <Input type="text" v-model="form.proxmox_user" class="mt-1 block w-full font-mono" />
                <InputError class="mt-2" :message="form.errors.proxmox_user" />
              </div>
              <div class="flex flex-col gap-2">
                <Label class="text-xs font-medium text-muted-foreground uppercase">Token ID</Label>
                <Input type="text" v-model="form.proxmox_token_id" class="mt-1 block w-full font-mono" />
                <InputError class="mt-2" :message="form.errors.proxmox_token_id" />
              </div>
              <div class="relative flex flex-col gap-2 lg:col-span-3">
                <Label class="text-xs font-medium text-muted-foreground uppercase">Secret</Label>
                <SecretInput id="proxmox_secret" v-model="form.proxmox_secret" class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.proxmox_secret" />
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
