<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { dashboardData } from './data';
import SectionLabel from './SectionLabel.vue';
import type { WidgetMode } from './widgets/widgetMode';
import WidgetShell from './widgets/WidgetShell.vue';

withDefaults(defineProps<{ mode?: WidgetMode }>(), { mode: 'desktop' });
</script>

<template>
    <WidgetShell :mode="mode">
        <div class="flex h-full flex-col rounded-xl border bg-card/70 p-3.5 text-card-foreground shadow-xl backdrop-blur-xl transition-all duration-200 hover:border-primary/50">
            <SectionLabel icon="mail" text="Inboxes" />
            <div class="space-y-2 widget-md:grid widget-md:grid-cols-[repeat(auto-fill,minmax(12rem,1fr))] widget-md:gap-2 widget-md:space-y-0">
                <div v-for="inbox in dashboardData.inboxes" :key="inbox.name"
                    class="flex items-center justify-between widget-md:rounded-lg widget-md:border widget-md:bg-background/40 widget-md:p-3">
                    <span class="flex items-center gap-1.5 text-[13px] text-slate-300 widget-md:gap-2.5">
                        <span class="flex shrink-0 items-center justify-center widget-md:h-8 widget-md:w-8 widget-md:rounded-lg widget-md:bg-muted/60">
                            <Icon :icon="`lucide:${inbox.icon}`" class="h-3 w-3 widget-md:h-4 widget-md:w-4" :class="inbox.iconColor" />
                        </span>
                        <span class="flex flex-col">
                            {{ inbox.name }}
                            <span class="hidden text-[11px] text-muted-foreground widget-md:inline">
                                {{ inbox.unread === 0 ? 'All caught up' : 'unread messages' }}
                            </span>
                        </span>
                    </span>
                    <span class="rounded-full px-1.5 py-0.5 text-[12px] font-bold widget-md:px-2.5 widget-md:py-1 widget-md:text-base"
                        :style="{ background: inbox.bg, color: inbox.color, border: '1px solid ' + inbox.border }">
                        {{ inbox.unread }}
                    </span>
                </div>
            </div>
        </div>
    </WidgetShell>
</template>
