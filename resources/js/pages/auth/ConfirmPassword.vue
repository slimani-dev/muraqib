<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/GuestLayout.vue';
import { store } from '@/routes/password/confirm';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(store.url(), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <AuthLayout title="Confirm your password"
        description="This is a secure area of the application. Please confirm your password before continuing.">

        <Head title="Confirm password" />

        <form @submit.prevent="submit">
            <div class="space-y-6">
                <div class="grid gap-2">
                    <Label htmlFor="password">Password</Label>
                    <Input id="password" type="password" v-model="form.password" class="mt-1 block w-full" required
                        autocomplete="current-password" autofocus />

                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center">
                    <Button class="w-full" :disabled="form.processing" data-test="confirm-password-button">
                        <Spinner v-if="form.processing" class="mr-2" />
                        Confirm Password
                    </Button>
                </div>
            </div>
        </form>
    </AuthLayout>
</template>
