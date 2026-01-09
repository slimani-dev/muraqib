<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import routeSettings from '@/routes/settings';
import { disable, enable, show } from '@/routes/two-factor';
import { sidebarNavItems } from '@/layouts/settings/items';
import { NavItem } from '@/types';
import { Form, Head } from '@inertiajs/vue3';
import { ShieldBan, ShieldCheck } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';

interface Props {
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
}

withDefaults(defineProps<Props>(), {
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const navItems: NavItem[] = sidebarNavItems;

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => {
    clearTwoFactorAuthData();
});
</script>

<template>
    <AppLayout :breadcrumbs="[
        { title: 'Settings', href: routeSettings.general.url() },
        { title: 'Two-Factor Authentication', href: '' },
    ]">

        <Head title="Two-Factor Authentication" />

        <h1 class="sr-only">Two-Factor Authentication Settings</h1>

        <SettingsLayout :nav-items="navItems">
            <Card class="bg-white border-slate-200 dark:bg-surface-dark/40 dark:border-slate-800 backdrop-blur-sm">
                <CardHeader class="border-b border-border p-4">
                    <CardTitle class="text-base">Two-Factor Authentication</CardTitle>
                    <CardDescription>Manage your two-factor authentication settings</CardDescription>
                </CardHeader>
                <CardContent class="p-6">
                    <div v-if="!twoFactorEnabled" class="flex flex-col items-start justify-start space-y-4">
                        <Badge variant="destructive">Disabled</Badge>

                        <p class="text-muted-foreground">
                            When you enable two-factor authentication, you will be
                            prompted for a secure pin during login. This pin can be
                            retrieved from a TOTP-supported application on your
                            phone.
                        </p>

                        <div>
                            <Button v-if="hasSetupData" @click="showSetupModal = true">
                                <ShieldCheck />Continue Setup
                            </Button>
                            <Form v-else v-bind="enable.form()" @success="showSetupModal = true"
                                #default="{ processing }">
                                <Button type="submit" :disabled="processing">
                                    <ShieldCheck />Enable 2FA
                                </Button>
                            </Form>
                        </div>
                    </div>

                    <div v-else class="flex flex-col items-start justify-start space-y-4">
                        <Badge variant="default">Enabled</Badge>

                        <p class="text-muted-foreground">
                            With two-factor authentication enabled, you will be
                            prompted for a secure, random pin during login, which
                            you can retrieve from the TOTP-supported application on
                            your phone.
                        </p>

                        <TwoFactorRecoveryCodes />

                        <div class="relative inline">
                            <Form v-bind="disable.form()" #default="{ processing }">
                                <Button variant="destructive" type="submit" :disabled="processing">
                                    <ShieldBan />
                                    Disable 2FA
                                </Button>
                            </Form>
                        </div>
                    </div>

                    <TwoFactorSetupModal v-model:isOpen="showSetupModal" :requiresConfirmation="requiresConfirmation"
                        :twoFactorEnabled="twoFactorEnabled" />
                </CardContent>
            </Card>
        </SettingsLayout>
    </AppLayout>
</template>
