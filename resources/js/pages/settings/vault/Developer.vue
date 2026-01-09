<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { sidebarNavItems } from '@/layouts/settings/items';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';
import {
    Card,
    CardContent,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import SecretInput from '@/components/Settings/SecretInput.vue';
import routeSettings from '@/routes/settings';
import { NavItem } from '@/types';

const props = defineProps({
    settings: {
        type: Object,
        required: true,
    }
});

const form = useForm({
    ...props.settings // spread developer settings
});

const submit = () => {
    form.put(routeSettings.developer.update.url(), {
        preserveScroll: true,
    });
};

const navItems: NavItem[] = sidebarNavItems;
</script>

<template>
    <AppLayout :breadcrumbs="[
        { title: 'Settings', href: routeSettings.general.url() },
        { title: 'Developer', href: '' },
    ]">

        <Head title="Developer Settings" />

        <SettingsLayout :nav-items="navItems">
            <div class="space-y-6">
                <HeadingSmall title="Developer Settings" description="Configure advanced developer options" />

                <form @submit.prevent="submit" class="space-y-6">
                    <Card
                        class="bg-white border-slate-200 dark:bg-surface-dark/40 dark:border-slate-800 backdrop-blur-sm">
                        <CardHeader class="border-b border-border p-4">
                            <CardTitle class="text-base">Developer Settings</CardTitle>
                        </CardHeader>
                        <CardContent class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex flex-col gap-2">
                                <Label>GitHub Token</Label>
                                <SecretInput v-model="form.github_token" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.github_token" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <Label>PostHog Project Key</Label>
                                <SecretInput v-model="form.posthog_project_key" class="mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.posthog_project_key" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <Label>PostHog Host</Label>
                                <Input v-model="form.posthog_host" class="font-mono mt-1 block w-full" />
                                <InputError class="mt-2" :message="form.errors.posthog_host" />
                            </div>
                        </CardContent>
                    </Card>

                    <div class="flex items-center gap-4 justify-end">
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
