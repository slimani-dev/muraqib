<script setup lang="ts">
import { ref } from 'vue';
// @ts-expect-error: axios is globally available via window.axios
import axios from 'axios';
import { Loader2, CheckCircle, XCircle } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

const props = defineProps({
    service: {
        type: String,
        required: true,
    },
    payload: {
        type: Object,
        required: true,
    },
});

const status = ref('idle'); // idle, testing, success, fail
const errorMessage = ref('');

const testConnection = async () => {
    status.value = 'testing';
    errorMessage.value = '';

    try {
        // @ts-expect-error: route is globally available via Ziggy
        await axios.post(route('settings.test-connection'), {
            service: props.service,
            payload: props.payload,
        });
        status.value = 'success';
        setTimeout(() => {
            if (status.value === 'success') status.value = 'idle';
        }, 3000);
    } catch (error: any) {
        status.value = 'fail';
        errorMessage.value = error.response?.data?.message || 'Connection failed.';
        setTimeout(() => {
            if (status.value === 'fail') status.value = 'idle';
        }, 5000);
    }
};
</script>

<template>
    <div class="flex items-center gap-2">
        <Badge v-if="status === 'success'" variant="outline"
            class="bg-emerald-500/10 text-emerald-500 border-emerald-500/20 gap-1.5">
            <CheckCircle class="h-3.5 w-3.5" />
            Connected
        </Badge>
        <Badge v-if="status === 'fail'" variant="outline" class="bg-red-500/10 text-red-500 border-red-500/20 gap-1.5">
            <XCircle class="h-3.5 w-3.5" />
            Failed
        </Badge>

        <Button variant="ghost" size="sm" class="text-primary hover:text-primary/80 hover:bg-primary/10"
            @click="testConnection" :disabled="status === 'testing'">
            <Loader2 v-if="status === 'testing'" class="h-3 w-3 animate-spin mr-2" />
            Test Connection
        </Button>
    </div>
</template>
