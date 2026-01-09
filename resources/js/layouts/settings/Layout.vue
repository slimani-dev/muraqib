<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useActiveUrl } from '@/composables/useActiveUrl';
import { toUrl } from '@/lib/utils';
import { Link } from '@inertiajs/vue3';
import { type NavItem } from '@/types';

import { sidebarNavItems } from './items';

export interface Props {
    navItems?: NavItem[];
}

const props = withDefaults(defineProps<Props>(), {
    navItems: () => sidebarNavItems,
});

const { urlIsActive } = useActiveUrl();
</script>

<template>
    <div class="px-4 py-6">

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1 space-x-0" aria-label="Settings">
                    <Button v-for="item in props.navItems" :key="toUrl(item.href)" variant="ghost" :class="[
                        'w-full justify-start',
                        urlIsActive(item.href) ? 'bg-muted hover:bg-muted text-primary' : 'text-muted-foreground',
                    ]" as-child>
                        <Link :href="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1">
                <section class="space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
