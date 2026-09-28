<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { Button } from '@/components/ui/button';

defineProps<{
    /** The widget catalog takes a 20rem column on the right on desktop; stay centred on what's left. */
    sheetOpen: boolean;
    allCollapsed: boolean;
    pageName: string;
    isDefaultPage: boolean;
    dirty: boolean;
    saving: boolean;
    error: string | null;
}>();

const emit = defineEmits<{
    rename: [name: string];
    widgets: [];
    toggleCollapse: [];
    reset: [];
    delete: [];
    cancel: [];
    save: [];
}>();
</script>

<template>
    <div class="fixed bottom-4 left-1/2 z-40 flex w-[calc(100%-2rem)] max-w-3xl -translate-x-1/2 flex-wrap items-center gap-2 rounded-2xl border bg-card/95 p-2 shadow-2xl backdrop-blur-xl transition-[left,width] duration-300"
        :class="{ 'md:left-[calc(50%-10rem)] md:w-[calc(100%-22rem)]': sheetOpen }">
        <div class="flex min-w-0 flex-1 items-center gap-1.5">
            <Icon icon="lucide:pencil" class="ml-1 h-4 w-4 shrink-0 text-primary" />
            <input :value="pageName" maxlength="40" aria-label="Page name" title="Page name (shown in the header)"
                class="h-8 min-w-0 flex-1 rounded-lg border border-border/60 bg-background/60 px-2.5 text-sm font-semibold outline-none focus:border-primary/60"
                @input="emit('rename', ($event.target as HTMLInputElement).value)" />
        </div>
        <Button variant="outline" size="sm" class="cursor-pointer" @click="emit('widgets')">
            <Icon icon="lucide:layout-grid" class="h-4 w-4" /> Widgets
        </Button>
        <Button variant="ghost" size="sm" class="cursor-pointer" :title="allCollapsed ? 'Show every widget' : 'Show only widget toolbars, to rearrange without scrolling'" @click="emit('toggleCollapse')">
            <Icon :icon="allCollapsed ? 'lucide:chevrons-up-down' : 'lucide:chevrons-down-up'" class="h-4 w-4" /> {{ allCollapsed ? 'Expand all' : 'Collapse all' }}
        </Button>
        <Button variant="ghost" size="sm" class="cursor-pointer" :title="isDefaultPage ? 'Back to the built-in layout' : 'Empty this page'" @click="emit('reset')">
            <Icon icon="lucide:rotate-ccw" class="h-4 w-4" /> Reset
        </Button>
        <Button v-if="!isDefaultPage" variant="ghost" size="sm" class="cursor-pointer text-destructive hover:text-destructive" @click="emit('delete')">
            <Icon icon="lucide:trash-2" class="h-4 w-4" /> Delete page
        </Button>
        <Button variant="ghost" size="sm" class="cursor-pointer" @click="emit('cancel')">Cancel</Button>
        <Button size="sm" class="cursor-pointer" :disabled="saving || !pageName.trim()" @click="emit('save')">
            <Icon v-if="saving" icon="lucide:loader-2" class="h-4 w-4 animate-spin" />
            Save<span v-if="dirty" class="ml-0.5">*</span>
        </Button>
        <p v-if="error" class="w-full px-1 text-xs text-destructive">{{ error }}</p>
    </div>
</template>
