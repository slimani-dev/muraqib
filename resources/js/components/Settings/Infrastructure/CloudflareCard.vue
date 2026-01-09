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
        cloudflare_email: string;
        cloudflare_account_id: string;
        cloudflare_api_token?: string;
    };
}

const props = defineProps<Props>();

const form = useForm({
    cloudflare_email: props.settings.cloudflare_email,
    cloudflare_account_id: props.settings.cloudflare_account_id,
    cloudflare_api_token: props.settings.cloudflare_api_token,
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
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#F38020]/10 text-[#F38020]">
                        <span class="material-symbols-outlined">cloud</span>
                    </div>
                    <div>
                        <CardTitle class="text-base">Cloudflare DNS</CardTitle>
                        <CardDescription>Managing DNS records for automatic SSL.</CardDescription>
                    </div>
                </div>
                <!-- Assuming Cloudflare also supports connection testing via the generic button for now -->
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
                    <SecretInput id="cloudflare_api_token" v-model="form.cloudflare_api_token"
                        class="mt-1 block w-full" />
                    <InputError class="mt-2" :message="form.errors.cloudflare_api_token" />
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
