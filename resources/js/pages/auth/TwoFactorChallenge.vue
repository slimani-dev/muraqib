<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/GuestLayout.vue';
import { store } from '@/routes/two-factor/login';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface AuthConfigContent {
    title: string;
    description: string;
    toggleText: string;
}

const authConfigContent = computed<AuthConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: 'Recovery Code',
            description:
                'Please confirm access to your account by entering one of your emergency recovery codes.',
            toggleText: 'login using an authentication code',
        };
    }

    return {
        title: 'Authentication Code',
        description:
            'Enter the authentication code provided by your authenticator application.',
        toggleText: 'login using a recovery code',
    };
});

const showRecoveryInput = ref<boolean>(false);

const toggleRecoveryMode = (): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    form.clearErrors();
    form.reset();
    code.value = '';
};

const code = ref<string>('');
const form = useForm({
    code: '',
    recovery_code: '',
});

const submit = () => {
    form.code = code.value;
    form.post(store.url(), {
        onError: () => {
            code.value = '';
            form.reset('code', 'recovery_code');
        }
    });
};
</script>

<template>
    <AuthLayout :title="authConfigContent.title" :description="authConfigContent.description">

        <Head title="Two-Factor Authentication" />

        <div class="space-y-6">
            <template v-if="!showRecoveryInput">
                <form @submit.prevent="submit" class="space-y-4">
                    <input type="hidden" name="code" :value="code" />
                    <div class="flex flex-col items-center justify-center space-y-3 text-center">
                        <div class="flex w-full items-center justify-center">
                            <InputOTP id="otp" v-model="code" :maxlength="6" :disabled="form.processing" autofocus>
                                <InputOTPGroup>
                                    <InputOTPSlot v-for="index in 6" :key="index" :index="index - 1" />
                                </InputOTPGroup>
                            </InputOTP>
                        </div>
                        <InputError :message="form.errors.code" />
                    </div>
                    <Button type="submit" class="w-full" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="mr-2" />
                        Continue
                    </Button>
                    <div class="text-center text-sm text-muted-foreground">
                        <span>or you can </span>
                        <button type="button"
                            class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            @click="toggleRecoveryMode">
                            {{ authConfigContent.toggleText }}
                        </button>
                    </div>
                </form>
            </template>

            <template v-else>
                <form @submit.prevent="submit" class="space-y-4">
                    <Input name="recovery_code" type="text" v-model="form.recovery_code"
                        placeholder="Enter recovery code" :autofocus="showRecoveryInput" required />
                    <InputError :message="form.errors.recovery_code" />
                    <Button type="submit" class="w-full" :disabled="form.processing">
                        <Spinner v-if="form.processing" class="mr-2" />
                        Continue
                    </Button>

                    <div class="text-center text-sm text-muted-foreground">
                        <span>or you can </span>
                        <button type="button"
                            class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            @click="toggleRecoveryMode">
                            {{ authConfigContent.toggleText }}
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </AuthLayout>
</template>
