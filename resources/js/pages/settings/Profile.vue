<script setup lang="ts">
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { sidebarNavItems } from '@/layouts/settings/items';
import routeSettings from '@/routes/settings';
import { NavItem } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const navItems: NavItem[] = sidebarNavItems;

const page = usePage<any>();
const user = page.props.auth.user;
const mustVerifyEmail = page.props.mustVerifyEmail;
const status = page.props.status;

const form = useForm({
    name: user.name,
    email: user.email,
});

const submit = () => {
    form.patch(ProfileController.update.url());
};
</script>

<template>
    <AppLayout :breadcrumbs="[
        { title: 'Settings', href: routeSettings.general.url() },
        { title: 'Profile', href: '' },
    ]">

        <Head title="Profile settings" />

        <h1 class="sr-only">Profile Settings</h1>

        <SettingsLayout :nav-items="navItems">
            <div class="flex flex-col space-y-6">
                <Card class="bg-white border-slate-200 dark:bg-surface-dark/40 dark:border-slate-800 backdrop-blur-sm">
                    <CardHeader class="border-b border-border p-4">
                        <CardTitle class="text-base">Profile information</CardTitle>
                        <CardDescription>Update your name and email address</CardDescription>
                    </CardHeader>
                    <CardContent class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="flex flex-col gap-2">
                                <Label for="name">Name</Label>
                                <Input id="name" class="mt-1 block w-full" v-model="form.name" required
                                    autocomplete="name" placeholder="Full name" />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="email">Email address</Label>
                                <Input id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                                    autocomplete="username" placeholder="Email address" />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <div v-if="mustVerifyEmail && !user.email_verified_at">
                                <p class="-mt-4 text-sm text-muted-foreground">
                                    Your email address is unverified.
                                    <Link :href="send().url" method="post" as="button"
                                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500">
                                        Click here to resend the verification email.
                                    </Link>
                                </p>

                                <div v-if="status === 'verification-link-sent'"
                                    class="mt-2 text-sm font-medium text-green-600">
                                    A new verification link has been sent to your email
                                    address.
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <Button :disabled="form.processing" data-test="update-profile-button">Save</Button>

                                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                    <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">
                                        Saved.
                                    </p>
                                </Transition>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
