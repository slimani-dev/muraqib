<script setup lang="ts">
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
import { useForm } from '@inertiajs/vue3';
import routeSettings from '@/routes/settings';

interface Props {
    settings: {
        proxmox_url: string;
        proxmox_user: string;
        proxmox_token_id: string;
        proxmox_secret?: string;
    };
}

const props = defineProps<Props>();

const form = useForm({
    proxmox_url: props.settings.proxmox_url,
    proxmox_user: props.settings.proxmox_user,
    proxmox_token_id: props.settings.proxmox_token_id,
    proxmox_secret: props.settings.proxmox_secret,
});

const submit = () => {
    form.put(routeSettings.infrastructure.update.url(), {
        preserveScroll: true,
    });
};
</script>

<template>
    <form @submit.prevent="submit">
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

            <div
                class="border-t border-border p-4 flex items-center justify-end gap-4 bg-slate-50/50 dark:bg-transparent">
                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">
                        Saved.
                    </p>
                </Transition>

                <Button type="submit" :disabled="form.processing">Save Changes</Button>
            </div>
        </Card>
    </form>
</template>
