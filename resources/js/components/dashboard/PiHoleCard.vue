<script setup lang="ts">
import { dashboardData } from './data';
import SectionLabel from './SectionLabel.vue';
import type { WidgetMode } from './widgets/widgetMode';
import WidgetShell from './widgets/WidgetShell.vue';

withDefaults(defineProps<{ mode?: WidgetMode }>(), { mode: 'desktop' });
</script>

<template>
    <WidgetShell :mode="mode">
        <div class="flex h-full flex-col rounded-xl border bg-card/70 p-3.5 text-card-foreground shadow-xl backdrop-blur-xl transition-all duration-200 hover:border-primary/50">
            <SectionLabel icon="shield" text="Pi-hole DNS" />
            <div class="widget-md:grid widget-md:grid-cols-[9rem_9rem_1fr] widget-md:items-center widget-md:gap-3">
                <div class="mb-2 grid grid-cols-2 gap-2 widget-md:contents">
                    <div class="rounded-lg border border-border/10 bg-muted p-2 text-center widget-md:p-3">
                        <div class="font-mono text-sm font-bold text-foreground widget-md:text-2xl">{{ dashboardData.pihole.queries }}</div>
                        <div class="text-[11px] text-muted-foreground">Queries</div>
                    </div>
                    <div class="rounded-lg border border-destructive/15 bg-destructive/5 p-2 text-center widget-md:p-3">
                        <div class="font-mono text-sm font-bold text-destructive widget-md:text-2xl">{{ dashboardData.pihole.blockedPercent }}%</div>
                        <div class="text-[11px] text-muted-foreground">Blocked</div>
                    </div>
                </div>
                <div>
                    <div class="mb-1 hidden justify-between font-mono text-[12px] text-muted-foreground widget-md:flex">
                        <span>Blocked share of queries</span>
                        <span class="text-destructive">{{ dashboardData.pihole.blockedPercent }}%</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-muted widget-md:h-2.5">
                        <div class="h-full rounded-full bg-gradient-to-r from-destructive to-chart-4 transition-all duration-1000 ease-out"
                            :style="{ width: dashboardData.pihole.blockedPercent + '%' }"></div>
                    </div>
                    <div class="mt-1 font-mono text-[12px] text-muted-foreground">
                        {{ dashboardData.pihole.blockedToday }} blocked today
                    </div>
                </div>
            </div>
        </div>
    </WidgetShell>
</template>
