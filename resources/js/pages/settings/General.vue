<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { sidebarNavItems } from '@/layouts/settings/items';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader, // unused but kept for consistency if needed later? No let's clean up.
  CardTitle, // unused
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import { ChevronsUpDown, Check } from 'lucide-vue-next';
import routeSettings from '@/routes/settings';
import { NavItem } from '@/types';

const props = defineProps({
  settings: {
    type: Object,
    required: true,
  },
  timezones: {
    type: Array as () => Array<{ value: string; label: string; time: string }>,
    default: () => [],
  },
  defaults: {
    type: Object as () => { site_name: string; root_domain: string },
    default: () => ({}),
  },
});

const form = useForm({
  site_name: props.settings.site_name,
  root_domain: props.settings.root_domain,
  timezone: props.settings.timezone,
  puid: props.settings.puid,
  pgid: props.settings.pgid,
});

const openTimezone = ref(false);

const submit = () => {
  form.put(routeSettings.general.update.url(), {
    preserveScroll: true,
  });
};

const navItems: NavItem[] = sidebarNavItems;
</script>

<template>
  <AppLayout :breadcrumbs="[
    { title: 'Settings', href: routeSettings.general.url() },
    { title: 'Global Settings', href: '' },
  ]">

    <Head title="General Settings" />

    <SettingsLayout :nav-items="navItems">
      <div class="space-y-6">
        <HeadingSmall title="Global Settings" description="Update your application's global configuration" />

        <form @submit.prevent="submit" class="space-y-6">
          <!-- Application Settings Card -->
          <Card>
            <CardHeader class="border-b border-border p-4">
              <CardTitle class="text-base">Application Settings</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-6 p-6 md:grid-cols-2">
              <!-- Site Name -->
              <div class="flex flex-col gap-2">
                <Label for="site_name">Site Name</Label>
                <Input id="site_name" v-model="form.site_name" :placeholder="defaults.site_name"
                  class="mt-1 block w-full" />
                <InputError class="mt-2" :message="form.errors.site_name" />
              </div>

              <!-- Root Domain -->
              <div class="flex flex-col gap-2">
                <Label for="root-domain">Root Domain</Label>
                <div class="mt-1 flex w-full items-center">
                  <span
                    class="flex h-10 items-center rounded-l-md border border-r-0 border-input bg-muted px-3 text-sm text-muted-foreground">https://</span>
                  <Input id="root-domain" v-model="form.root_domain" class="block w-full rounded-l-none"
                    :placeholder="defaults.root_domain" />
                </div>
                <InputError class="mt-2" :message="form.errors.root_domain" />
              </div>

              <!-- Timezone -->
              <div class="flex flex-col gap-2 md:col-span-2">
                <Label for="timezone">Timezone</Label>
                <Popover v-model:open="openTimezone">
                  <PopoverTrigger as-child>
                    <Button variant="outline" role="combobox" :aria-expanded="openTimezone"
                      class="mt-1 flex w-full justify-between text-left font-normal"
                      :class="!form.timezone && 'text-muted-foreground'">
                      {{
                        form.timezone
                          ? timezones.find((tz) => tz.value === form.timezone)
                            ?.label
                          : 'Select timezone...'
                      }}
                      <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                    </Button>
                  </PopoverTrigger>
                  <PopoverContent class="w-[400px] p-0" align="start">
                    <Command>
                      <CommandInput placeholder="Search timezone..." />
                      <CommandEmpty>No timezone found.</CommandEmpty>
                      <CommandList>
                        <CommandGroup>
                          <CommandItem v-for="timezone in timezones" :key="timezone.value" :value="timezone.value"
                            @select="
                              () => {
                                form.timezone = timezone.value;
                                openTimezone = false;
                              }
                            ">
                            <Check class="mr-2 h-4 w-4" :class="form.timezone === timezone.value
                              ? 'opacity-100'
                              : 'opacity-0'
                              " />
                            <div class="flex flex-col">
                              <span>{{ timezone.label }}</span>
                              <span class="text-xs text-muted-foreground">Current Time: {{ timezone.time }}</span>
                            </div>
                          </CommandItem>
                        </CommandGroup>
                      </CommandList>
                    </Command>
                  </PopoverContent>
                </Popover>
                <InputError class="mt-2" :message="form.errors.timezone" />
              </div>
            </CardContent>
          </Card>

          <!-- System Settings Card -->
          <Card>
            <CardHeader class="border-b border-border p-4">
              <CardTitle class="text-base">System Settings</CardTitle>
            </CardHeader>
            <CardContent class="p-6">
              <!-- PUID/PGID -->
              <div class="flex flex-col gap-2">
                <Label for="puid">PUID / PGID</Label>
                <div class="mt-1 flex gap-4">
                  <Input id="puid" type="number" v-model="form.puid" placeholder="PUID" class="block w-full" />
                  <Input id="pgid" type="number" v-model="form.pgid" placeholder="PGID" class="block w-full" />
                </div>
                <p class="text-[0.8rem] text-muted-foreground">
                  User/Group ID for Docker volumes.
                </p>
              </div>
            </CardContent>
          </Card>

          <!-- Appearance Card -->
          <Card>
            <CardHeader class="border-b border-border p-4">
              <CardTitle class="text-base">Appearance</CardTitle>
            </CardHeader>
            <CardContent class="p-6">
              <div class="flex flex-col gap-2">
                <AppearanceTabs />
                <p class="text-[0.8rem] text-muted-foreground">
                  Customize the look and feel of the application.
                </p>
              </div>
            </CardContent>
          </Card>

          <div class="flex items-center gap-4">
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
