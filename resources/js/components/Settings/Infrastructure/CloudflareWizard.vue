<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import axios from 'axios';
import routeSettings from '@/routes/settings'; // Wayfinder

// Props
defineProps<{
    open?: boolean
}>();

const emit = defineEmits(['update:open']);

// State
const step = ref(1);
const isLoading = ref(false);
const error = ref<string | null>(null);
const tunnelInfo = ref<{ tunnel_id: string; tunnel_token: string } | null>(null);
const zones = ref<any[]>([]);
const installMethod = ref<'docker' | 'manual'>('docker');

// Forms
const authForm = reactive({
    account_id: '',
    api_token: '',
});

const ingressForm = reactive({
    services: [
        { hostname: 'muraqib.example.com', service: 'http://localhost:80' },
        { hostname: 'netdata.example.com', service: 'http://192.168.1.199:19999' },
        { hostname: 'portainer.example.com', service: 'http://192.168.1.5:9000' },
    ],
    zone_id: '',
});

// Steps Logic
const nextStep = () => {
    step.value++;
    error.value = null;
};

const verifyAuth = async () => {
    isLoading.value = true;
    error.value = null;
    try {
        await axios.post(routeSettings.cloudflare.verify.url(), authForm);
        nextStep();
        createTunnel();
    } catch (e: any) {
        error.value = e.response?.data?.message || 'Authentication failed';
    } finally {
        isLoading.value = false;
    }
};

const createTunnel = async () => {
    isLoading.value = true;
    try {
        const res = await axios.post(routeSettings.cloudflare.tunnel.url());
        tunnelInfo.value = res.data;
        fetchZones();
    } catch (e: any) {
        error.value = 'Failed to create tunnel: ' + (e.response?.data?.message || e.message);
    } finally {
        isLoading.value = false;
    }
};

const fetchZones = async () => {
    try {
        const res = await axios.get(routeSettings.cloudflare.zones.url());
        zones.value = res.data;
        if (zones.value.length > 0) {
            ingressForm.zone_id = zones.value[0].id;
            const domain = zones.value[0].name;
            ingressForm.services = [
                { hostname: `dashboard.${domain}`, service: 'http://localhost:80' },
                { hostname: `netdata.${domain}`, service: 'http://192.168.1.199:19999' },
                { hostname: `portainer.${domain}`, service: 'http://192.168.1.5:9000' },
            ];
        }
    } catch (e) {
        console.error("Failed to fetch zones", e);
    }
};

const saveRouting = async () => {
    isLoading.value = true;
    try {
        await axios.post(routeSettings.cloudflare.ingress.url(), ingressForm);
        step.value = 5;
    } catch (e: any) {
        error.value = 'Failed to update routing: ' + (e.response?.data?.message || e.message);
    } finally {
        isLoading.value = false;
    }
};

// Clipboard
const copyToClipboard = (text: string) => {
    navigator.clipboard.writeText(text);
};
</script>

<template>
    <Dialog modal>
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent class="sm:max-w-[700px]">
            <DialogHeader>
                <DialogTitle>Cloudflare Tunnel Wizard</DialogTitle>
                <DialogDescription>
                    Connect your server securely to the internet without opening ports.
                </DialogDescription>
            </DialogHeader>

            <!-- Error Alert -->
            <Alert v-if="error" variant="destructive" class="mb-4">
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{{ error }}</AlertDescription>
            </Alert>

            <!-- Step 1: Auth -->
            <!-- Step 1: Auth -->
            <div v-if="step === 1" class="space-y-4 py-4">
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-400 flex gap-3 items-start">
                    <span class="material-symbols-outlined mt-0.5 shrink-0">info</span>
                    <div>
                        <h5 class="font-medium mb-1 leading-none tracking-tight">First time?</h5>
                        <div class="text-xs opacity-90">
                            We need your Cloudflare credentials to create a secure tunnel.
                        </div>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label class="flex items-center gap-2">
                        Cloudflare Account ID
                        <a href="https://dash.cloudflare.com" target="_blank" class="text-xs text-primary underline flex items-center">
                            Open Dashboard <span class="material-symbols-outlined text-[10px] ml-0.5">open_in_new</span>
                        </a>
                    </Label>
                    <Input v-model="authForm.account_id" placeholder="e.g. from URL: dash.cloudflare.com/YOUR_ACCOUNT_ID" />
                </div>

                <div class="grid gap-2">
                    <Label class="flex items-center gap-2">
                        API Token
                        <a href="https://dash.cloudflare.com/profile/api-tokens" target="_blank" class="text-xs text-primary underline flex items-center">
                            Create Token <span class="material-symbols-outlined text-[10px] ml-0.5">open_in_new</span>
                        </a>
                    </Label>
                    <Input v-model="authForm.api_token" type="password" placeholder="Cloudflare API Token" />
                    <div class="rounded-md bg-muted p-3 text-xs text-muted-foreground">
                        <p class="font-semibold mb-2">Required Permissions:</p>
                        <ul class="space-y-1.5">
                            <li class="flex items-center gap-1.5">
                                <span class="bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded">Account</span>
                                <span class="text-slate-400">/</span>
                                <span class="bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded">Cloudflare Tunnel</span>
                                <span class="text-slate-400">/</span>
                                <span class="font-medium text-primary">Edit</span>
                            </li>
                            <li class="flex items-center gap-1.5">
                                <span class="bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded">Zone</span>
                                <span class="text-slate-400">/</span>
                                <span class="bg-slate-200 dark:bg-slate-700 px-1.5 py-0.5 rounded">DNS</span>
                                <span class="text-slate-400">/</span>
                                <span class="font-medium text-primary">Edit</span>
                            </li>
                        </ul>
                        <p class="mt-2 text-[10px] opacity-70">
                            (Click "<span class="font-mono">+ Add more</span>" in Cloudflare to add multiple rows)
                        </p>
                    </div>
                </div>
            </div>

            <!-- Step 2: Tunnel Status (Auto-skipped if instant, but shown while loading) -->
            <div v-if="step === 2" class="space-y-4 py-4">
              <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-400 flex gap-3 items-start">
                <span class="material-symbols-outlined mt-0.5 shrink-0">info</span>
                <div>
                  <h5 class="font-medium mb-1 leading-none tracking-tight">First time?</h5>
                  <div class="text-xs opacity-90">
                    We need your Cloudflare credentials to create a secure tunnel.
                  </div>
                </div>
              </div>
                 <div v-if="isLoading" class="flex flex-col items-center justify-center space-y-4 py-8">
                     <span class="animate-spin material-symbols-outlined text-4xl text-primary">progress_activity</span>
                     <p>Creating Tunnel...</p>
                 </div>
                 <div v-else-if="tunnelInfo" class="space-y-4">
                     <Alert class="bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800">
                         <span class="material-symbols-outlined">check_circle</span>
                         <AlertTitle>Tunnel Created!</AlertTitle>
                         <AlertDescription>
                             Tunnel ID: <span class="font-mono text-xs">{{ tunnelInfo.tunnel_id }}</span>
                         </AlertDescription>
                     </Alert>
                     <p>Now, let's connect this server to the tunnel.</p>
                 </div>
            </div>

             <!-- Step 3: Installation -->
            <div v-if="step === 3 || (step === 2 && tunnelInfo && !isLoading)" class="space-y-4 py-4">
                 <!-- Custom Tabs -->
                 <div class="flex space-x-1 rounded-lg bg-slate-100 p-1 dark:bg-slate-800">
                    <button
                        @click="installMethod = 'docker'"
                        :class="[
                            'flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-all',
                            installMethod === 'docker' ? 'bg-white shadow dark:bg-slate-700' : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100'
                        ]"
                    >
                        Docker (Portainer)
                    </button>
                    <button
                        @click="installMethod = 'manual'"
                        :class="[
                            'flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-all',
                            installMethod === 'manual' ? 'bg-white shadow dark:bg-slate-700' : 'text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100'
                        ]"
                    >
                        Manual (CLI)
                    </button>
                 </div>

                 <div v-if="installMethod === 'docker'" class="space-y-4 pt-4">
                     <p class="text-sm text-muted-foreground">
                         If you have Portainer connected, we can auto-deploy this.
                     </p>
                     <div class="rounded-md bg-muted p-4 font-mono text-xs overflow-x-auto">
                         <pre>image: cloudflare/cloudflared:latest
command: tunnel run
environment:
  - TUNNEL_TOKEN={{ tunnelInfo?.tunnel_token }}
restart: always</pre>
                     </div>
                     <Button variant="outline" disabled>Auto-Deploy (Coming Soon)</Button>
                 </div>
                 <div v-if="installMethod === 'manual'" class="space-y-4 pt-4">
                     <p class="text-sm text-muted-foreground">Run this command on your server:</p>
                     <div class="relative rounded-md bg-black text-white p-4 font-mono text-xs break-all">
                         curl -L --output cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb &&
                         sudo dpkg -i cloudflared.deb &&
                         sudo cloudflared service install {{ tunnelInfo?.tunnel_token }}

                         <button @click="copyToClipboard(tunnelInfo?.tunnel_token || '')" class="absolute top-2 right-2 p-1 bg-white/10 rounded hover:bg-white/20">
                             <span class="material-symbols-outlined text-sm">content_copy</span>
                         </button>
                     </div>
                 </div>
            </div>

            <!-- Step 4: Routing -->
             <div v-if="step === 4" class="space-y-4 py-4">
                 <div class="grid gap-2">
                     <Label>Select Domain Zone</Label>
                     <select v-model="ingressForm.zone_id" class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50">
                         <option v-for="z in zones" :key="z.id" :value="z.id">{{ z.name }}</option>
                     </select>
                 </div>

                 <div class="space-y-2">
                     <Label>Service Mapping</Label>
                     <div v-for="(svc, index) in ingressForm.services" :key="index" class="flex gap-2">
                         <Input v-model="svc.hostname" placeholder="sub.domain.com" class="flex-1" />
                         <span class="flex items-center text-muted-foreground">-></span>
                         <Input v-model="svc.service" placeholder="http://ip:port" class="w-1/3" />
                         <Button variant="ghost" size="icon" @click="ingressForm.services.splice(index, 1)">
                             <span class="material-symbols-outlined text-destructive">delete</span>
                         </Button>
                     </div>
                     <Button variant="outline" size="sm" @click="ingressForm.services.push({ hostname: '', service: 'http://localhost:80' })">
                         + Add Service
                     </Button>
                 </div>
            </div>

            <!-- Step 5: Success -->
            <div v-if="step === 5" class="py-10 text-center space-y-4">
                <div class="flex justify-center">
                    <div class="rounded-full bg-green-100 p-3 text-green-600 dark:bg-green-900 dark:text-green-400">
                        <span class="material-symbols-outlined text-4xl">check</span>
                    </div>
                </div>
                <h3 class="text-lg font-semibold">Tunnel configured successfully!</h3>
                <p class="text-muted-foreground">Your services should now be accessible securely.</p>
            </div>

            <DialogFooter>
                <Button v-if="step === 1" @click="verifyAuth" :disabled="isLoading">
                    {{ isLoading ? 'Verifying...' : 'Next' }}
                </Button>
                <div v-if="step === 2 && !isLoading && tunnelInfo">
                    <Button @click="step = 3">Continue to Installation</Button>
                </div>
                <div v-if="step === 3">
                    <Button @click="step = 4">I have installed the agent</Button>
                </div>
                <div v-if="step === 4">
                    <Button @click="saveRouting" :disabled="isLoading">
                        {{ isLoading ? 'Publishing...' : 'Publish & Go Live' }}
                    </Button>
                </div>
                <div v-if="step === 5">
                     <DialogTrigger as-child>
                         <Button>Done</Button>
                     </DialogTrigger>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
