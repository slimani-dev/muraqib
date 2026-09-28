<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { ADMIN_REPOSITORIES_URL } from './repositories';

defineProps<{ text: string }>();
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-1.5 py-4 text-center">
        <Icon icon="lucide:git-branch" class="h-5 w-5 text-muted-foreground/50" />
        <p class="text-xs text-muted-foreground">{{ text }}</p>
        <a :href="ADMIN_REPOSITORIES_URL" class="text-xs font-medium text-primary hover:underline">Manage repositories</a>
    </div>
</template>
