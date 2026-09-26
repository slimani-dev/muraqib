<script setup lang="ts">


defineProps<{
    icon: string;
    text: string;
}>();
</script>

<template>
    <div
        class="mb-2.5 flex items-center gap-2 text-[0.72rem] font-bold tracking-[0.11em] text-muted-foreground uppercase after:h-px after:flex-1 after:bg-border/20 after:content-['']"
    >
        <i :data-lucide="icon" class="h-3 w-3"></i> {{ text }}
    </div>
</template>
