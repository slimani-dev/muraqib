<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { sidebarNavItems } from '@/layouts/settings/items';
import routeSettings from '@/routes/settings';
import { NavItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import CloudflareCard from '@/components/Settings/Infrastructure/CloudflareCard.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import { toast } from 'vue-sonner';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

const props = defineProps({
  settings: { type: Object, required: true },
  cloudflare_config: { type: Object, default: null },
});

const navItems: NavItem[] = sidebarNavItems;

// State
const isLoading = ref(false);
const ingressRules = ref<any[]>([]);
const dnsRecords = ref<any[]>([]);
const isAddingHost = ref(false);
const tunnelStatus = ref<{ status: string; connections: number; details: any } | null>(null);
const showOnlyMatched = ref(true);

// Add Host Form
const hostForm = useForm({
  hostname: '',
  service: 'http://localhost:80',
});

// Fetch Data
const fetchData = async () => {
  if (!props.cloudflare_config?.tunnel_id) return;

  isLoading.value = true;
  try {
    const [ingressRes, statusRes, recordsRes] = await Promise.all([
      axios.get(routeSettings.cloudflare.ingress.index.url()),
      axios.get(routeSettings.cloudflare.status.url()),
      axios.get(routeSettings.cloudflare.records.url()),
    ]);

    ingressRules.value = ingressRes.data;
    tunnelStatus.value = statusRes.data;
    dnsRecords.value = recordsRes.data;
  } catch (e) {
    console.error(e);
    toast.error('Failed to load tunnel details.');
  } finally {
    isLoading.value = false;
  }
};

onMounted(() => {
  fetchData();
});

// Actions
const addHost = () => {
  isAddingHost.value = false;

  const newRules = [...ingressRules.value, { hostname: hostForm.hostname, service: hostForm.service }];

  saveIngress(newRules);
  hostForm.reset();
};

const removeHost = (index: number) => {
  const newRules = [...ingressRules.value];
  newRules.splice(index, 1);
  saveIngress(newRules);
};

const saveIngress = async (rules: any[]) => {
  isLoading.value = true;
  try {
    await axios.post(routeSettings.cloudflare.ingress.update.url(), {
      services: rules,
      zone_id: props.cloudflare_config.domain_zone_id || 'manual-override',
    });
    ingressRules.value = rules;
    toast.success('Tunnel routing updated.');
    // Refresh DNS records after update as we might create new ones
    fetchData();
  } catch (e) {
    toast.error('Failed to update routing.');
    isLoading.value = false;
  }
};

// Computed
const filteredDnsRecords = computed(() => {
  // If filter is OFF, show all
  if (!showOnlyMatched.value) {
    return dnsRecords.value;
  }

  const tid = props.cloudflare_config?.tunnel_id;
  // If no tunnel ID to match against, show all (or none? showing all is safer to debug)
  if (!tid) return dnsRecords.value;

  return dnsRecords.value.filter((r) => {
    const content = r.content || '';
    return content.includes(tid);
  });
});
</script>

<template>
  <AppLayout
    :breadcrumbs="[
      { title: 'Settings', href: routeSettings.general.url() },
      { title: 'Cloudflare', href: '' },
    ]"
  >
    <Head title="Cloudflare Settings" />

    <SettingsLayout :nav-items="navItems">
      <div class="space-y-6">
        <div class="flex items-center justify-between">
          <HeadingSmall title="Cloudflare" description="Manage Cloudflare Tunnel and DNS" />
          <div
            v-if="tunnelStatus?.status === 'healthy'"
            class="flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"
          >
            <span class="relative flex h-2 w-2">
              <span
                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
              ></span>
              <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
            </span>
            Healthy ({{ tunnelStatus.connections }} Connections)
          </div>
          <div
            v-else-if="tunnelStatus?.status"
            class="flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700 uppercase dark:bg-amber-900/30 dark:text-amber-400"
          >
            {{ tunnelStatus.status }}
          </div>
        </div>

        <div class="space-y-6">
          <CloudflareCard :tunnels="tunnelStatus ? [tunnelStatus] : []" :settings="props.settings" />

          <!-- Details & DNS Tabs -->
          <div v-if="cloudflare_config?.tunnel_id" class="grid gap-6">
            <Tabs default-value="routes" class="w-full">
              <TabsList class="grid w-full grid-cols-2 lg:w-100">
                <TabsTrigger value="routes">Routes (Ingress)</TabsTrigger>
                <TabsTrigger value="dns">DNS Records</TabsTrigger>
              </TabsList>

              <!-- Routes Tab -->
              <TabsContent value="routes" class="mt-4">
                <Card class="border-slate-200 bg-white dark:border-slate-800 dark:bg-surface-dark/40">
                  <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                      <CardTitle class="text-base">Ingress Rules</CardTitle>
                      <CardDescription>Map public domains to local services.</CardDescription>
                    </div>
                    <Dialog v-model:open="isAddingHost">
                      <DialogTrigger as="child">
                        <Button size="sm" variant="outline">
                          <span class="material-symbols-outlined mr-2 text-[16px]">add</span>
                          Add Route
                        </Button>
                      </DialogTrigger>
                      <DialogContent>
                        <DialogHeader>
                          <DialogTitle>Add Public Host</DialogTitle>
                          <DialogDescription>
                            Route traffic from a public Cloudflare domain to a local service.
                          </DialogDescription>
                        </DialogHeader>
                        <div class="grid gap-4 py-4">
                          <div class="grid gap-2">
                            <Label>Public Hostname</Label>
                            <Input v-model="hostForm.hostname" placeholder="app.example.com" />
                          </div>
                          <div class="grid gap-2">
                            <Label>Local Service URL</Label>
                            <Input v-model="hostForm.service" placeholder="http://192.168.1.10:8080" />
                            <p class="text-[10px] text-muted-foreground">
                              Use 'http://localhost:port' for local services.
                            </p>
                          </div>
                        </div>
                        <DialogFooter>
                          <Button variant="secondary" @click="isAddingHost = false">Cancel</Button>
                          <Button @click="addHost" :disabled="!hostForm.hostname || !hostForm.service">
                            Add Route
                          </Button>
                        </DialogFooter>
                      </DialogContent>
                    </Dialog>
                  </CardHeader>
                  <CardContent class="space-y-0 divide-y divide-border">
                    <div v-if="isLoading" class="py-8 text-center text-sm text-muted-foreground">
                      <span class="animate-pulse">Loading configuration...</span>
                    </div>
                    <div v-else-if="ingressRules.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                      No active routes configured for this tunnel.
                    </div>
                    <div
                      v-else
                      v-for="(rule, index) in ingressRules"
                      :key="index"
                      class="flex items-center justify-between px-1 py-3"
                    >
                      <div class="space-y-0.5">
                        <div class="flex items-center gap-2 text-sm font-medium">
                          <span class="material-symbols-outlined text-[18px] text-slate-400">public</span>
                          {{ rule.hostname }}
                        </div>
                        <div class="flex items-center gap-1 pl-7 font-mono text-xs text-muted-foreground">
                          <span class="material-symbols-outlined text-[14px]">arrow_right_alt</span>
                          {{ rule.service }}
                        </div>
                      </div>
                      <Button
                        variant="ghost"
                        size="icon"
                        class="h-8 w-8 text-muted-foreground hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20"
                        @click="removeHost(index)"
                      >
                        <span class="material-symbols-outlined text-[18px]">delete</span>
                      </Button>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>

              <!-- DNS Tab -->
              <TabsContent value="dns" class="mt-4">
                <Card class="border-slate-200 bg-white dark:border-slate-800 dark:bg-surface-dark/40">
                  <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                      <CardTitle class="text-base">DNS Records</CardTitle>
                      <CardDescription>CNAME records pointing to this tunnel.</CardDescription>
                    </div>
                    <div class="flex items-center space-x-2">
                      <!-- Use :checked and @update:checked explicitly for reka-ui/radix-vue SwitchRoot compatibility -->
                      <!-- Use v-model:checked for reka-ui SwitchRoot compatibility -->
                      <Switch id="match-filter" v-model:checked="showOnlyMatched" />
                      <Label htmlFor="match-filter" class="text-sm font-normal text-muted-foreground"
                        >Show matched only</Label
                      >
                    </div>
                  </CardHeader>
                  <CardContent class="space-y-0 divide-y divide-border">
                    <div v-if="isLoading" class="py-8 text-center text-sm text-muted-foreground">
                      <span class="animate-pulse">Loading records...</span>
                    </div>
                    <div
                      v-else-if="filteredDnsRecords.length === 0"
                      class="py-8 text-center text-sm text-muted-foreground"
                    >
                      No DNS records matching criteria found.
                    </div>
                    <div
                      v-else
                      v-for="record in filteredDnsRecords"
                      :key="record.id"
                      class="flex items-center justify-between px-1 py-3"
                    >
                      <div class="space-y-0.5">
                        <div class="flex items-center gap-2 text-sm font-medium">
                          <span
                            class="rounded bg-slate-100 px-1.5 py-0.5 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400"
                          >
                            {{ record.type }}
                          </span>
                          {{ record.name }}
                        </div>
                        <div
                          class="max-w-[300px] truncate pl-9 font-mono text-xs text-muted-foreground"
                          :title="record.content"
                        >
                          points to {{ record.content }}
                        </div>
                      </div>
                      <div>
                        <Badge
                          v-if="record.content.includes(cloudflare_config.tunnel_id)"
                          variant="outline"
                          class="border-emerald-200 bg-emerald-50 text-emerald-600 dark:border-emerald-900 dark:bg-emerald-950/30"
                        >
                          Matches Tunnel
                        </Badge>
                        <Badge v-else variant="outline" class="text-xs text-muted-foreground opacity-70">
                          Different Target
                        </Badge>
                      </div>
                    </div>
                  </CardContent>
                </Card>
              </TabsContent>
            </Tabs>
          </div>
        </div>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
