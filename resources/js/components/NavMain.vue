<script setup lang="ts">
import { useActiveUrl } from '@/composables/useActiveUrl';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{
    items: NavItem[];
}>();

const { urlIsActive } = useActiveUrl();
</script>

<template>
    <nav class="flex flex-col gap-1">
        <template v-for="item in items" :key="item.title">
            <Link :href="item.href" :class="[
                'flex items-center gap-3 px-3 py-2 rounded-lg transition-colors group',
                'group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:p-2',
                urlIsActive(item.href)
                    ? 'bg-primary/10 text-primary'
                    : 'text-muted-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
            ]">
                <!-- Render Material Symbol icon as text if it's a string -->
                <span v-if="typeof item.icon === 'string'" class="material-symbols-outlined text-[20px]"
                    :class="{ 'icon-filled': urlIsActive(item.href) }">
                    {{ item.icon }}
                </span>
                <!-- Fallback for component icons if ever used -->
                <component v-else-if="item.icon" :is="item.icon" class="h-5 w-5" />

                <span class="text-sm font-medium group-data-[collapsible=icon]:hidden">
                    {{ item.title }}
                </span>
            </Link>
        </template>
    </nav>
</template>
