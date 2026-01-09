<script setup lang="ts">
defineProps<{
    server?: string;
    title?: string;
}>();
</script>

<template>
    <div>
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500/80 mb-1">
            <slot name="server">{{ server }}</slot>
        </p>
        <h3 class="text-gray-900 dark:text-white text-lg font-bold flex items-center gap-2">
            <slot name="icon"></slot>
            <slot name="title">{{ title }}</slot>
        </h3>
    </div>
</template>
