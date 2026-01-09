<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import SecretInput from '@/components/Settings/SecretInput.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import routeSettings from '@/routes/settings';
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import { Loader2, CheckCircle, XCircle, Plug } from 'lucide-vue-next';

interface Props {
    settings: {
        portainer_url: string;
        portainer_api_key?: string;
    };
}

const props = defineProps<Props>();

const form = useForm({
    portainer_url: props.settings.portainer_url,
    portainer_api_key: props.settings.portainer_api_key,
});

// Verification Logic
const verificationState = ref<'idle' | 'testing' | 'success' | 'error'>('idle');

// Watch for API key changes to reset verification info
watch(() => form.portainer_api_key, (newVal) => {
    // If it's different from the initial prop value (and not empty?), reset state.
    // Or just simple rule: any change resets state.
    if (newVal !== props.settings.portainer_api_key) {
        verificationState.value = 'idle';
    }
});

const testConnection = () => {
    verificationState.value = 'testing';
    axios.post('/api/test-connection/portainer', {
        url: form.portainer_url,
        key: form.portainer_api_key
    })
        .then(() => verificationState.value = 'success')
        .catch(() => verificationState.value = 'error');
};

const submit = () => {
    form.put(routeSettings.infrastructure.update.url(), {
        preserveScroll: true,
    });
};

const isDirty = computed(() => form.isDirty);
</script>

<template>
    <form @submit.prevent="submit">
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
                <div class="flex items-center gap-2">
                    <Badge v-if="verificationState === 'success'" variant="outline"
                        class="bg-emerald-500/10 text-emerald-500 border-emerald-500/20 gap-1.5">
                        <CheckCircle class="h-3.5 w-3.5" />
                        Connected
                    </Badge>
                    <Badge v-if="verificationState === 'error'" variant="outline"
                        class="bg-red-500/10 text-red-500 border-red-500/20 gap-1.5">
                        <XCircle class="h-3.5 w-3.5" />
                        Failed
                    </Badge>

                    <Button type="button" variant="ghost" size="sm"
                        class="text-primary hover:text-primary/80 hover:bg-primary/10" @click.prevent="testConnection"
                        :disabled="verificationState === 'testing'">
                        <Loader2 v-if="verificationState === 'testing'" class="mr-2 h-4 w-4 animate-spin" />
                        <Plug v-else class="mr-2 h-4 w-4" />
                        Test
                    </Button>
                </div>
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

            <!-- Card Footer for Save Button needed? No standard CardFooter. We'll add a div at bottom -->
            <div
                class="border-t border-border p-4 flex items-center justify-end gap-4 bg-slate-50/50 dark:bg-transparent">
                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">
                        Saved.
                    </p>
                </Transition>

                <Button type="submit" :disabled="form.processing || (verificationState !== 'success' && isDirty)">
                    Save Changes
                </Button>
            </div>
        </Card>
    </form>
</template>
