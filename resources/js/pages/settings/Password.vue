<script setup lang="ts">
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import InputError from '@/components/InputError.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Form, Head } from '@inertiajs/vue3';
import routeSettings from '@/routes/settings';
import { sidebarNavItems } from '@/layouts/settings/items';
import { NavItem } from '@/types';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';

const navItems: NavItem[] = sidebarNavItems;
</script>

<template>
    <AppLayout :breadcrumbs="[
        { title: 'Settings', href: routeSettings.general.url() },
        { title: 'Password', href: '' },
    ]">

        <Head title="Password settings" />

        <h1 class="sr-only">Password Settings</h1>

        <SettingsLayout :nav-items="navItems">
            <div class="space-y-6">
                <Card class="bg-white border-slate-200 dark:bg-surface-dark/40 dark:border-slate-800 backdrop-blur-sm">
                    <CardHeader class="border-b border-border p-4">
                        <CardTitle class="text-base">Update password</CardTitle>
                        <CardDescription>Ensure your account is using a long, random password to stay secure
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="p-6">
                        <Form v-bind="PasswordController.update.form()" :options="{
                            preserveScroll: true,
                        }" reset-on-success :reset-on-error="[
                            'password',
                            'password_confirmation',
                            'current_password',
                        ]" class="space-y-6" v-slot="{ errors, processing, recentlySuccessful }">
                            <div class="flex flex-col gap-2">
                                <Label for="current_password">Current password</Label>
                                <Input id="current_password" name="current_password" type="password"
                                    class="mt-1 block w-full" autocomplete="current-password"
                                    placeholder="Current password" />
                                <InputError :message="errors.current_password" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="password">New password</Label>
                                <Input id="password" name="password" type="password" class="mt-1 block w-full"
                                    autocomplete="new-password" placeholder="New password" />
                                <InputError :message="errors.password" />
                            </div>

                            <div class="flex flex-col gap-2">
                                <Label for="password_confirmation">Confirm password</Label>
                                <Input id="password_confirmation" name="password_confirmation" type="password"
                                    class="mt-1 block w-full" autocomplete="new-password"
                                    placeholder="Confirm password" />
                                <InputError :message="errors.password_confirmation" />
                            </div>

                            <div class="flex items-center gap-4">
                                <Button :disabled="processing" data-test="update-password-button">Save password</Button>

                                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                                    <p v-show="recentlySuccessful" class="text-sm text-neutral-600">
                                        Saved.
                                    </p>
                                </Transition>
                            </div>
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
