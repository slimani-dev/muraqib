<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { ref } from 'vue';
import type { RepositorySummary } from './repositories';

withDefaults(defineProps<{
    repository: Pick<RepositorySummary, 'logo_url' | 'provider_icon' | 'provider_label'>;
    size?: string;
}>(), {
    size: 'h-4 w-4',
});

/** Falls back to the Git host's icon when there's no logo URL or it doesn't load. */
const failed = ref(false);
</script>

<template>
    <img v-if="repository.logo_url && !failed" :src="repository.logo_url" alt="" class="shrink-0 rounded object-contain" :class="size"
        @error="failed = true" />
    <Icon v-else :icon="repository.provider_icon" class="shrink-0 text-muted-foreground" :class="size" :title="repository.provider_label" />
</template>
