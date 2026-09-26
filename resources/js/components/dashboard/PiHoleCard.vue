<script setup lang="ts">

import { dashboardData } from './data';
import SectionLabel from './SectionLabel.vue';
</script>

<template>
    <div class="flex h-full flex-col rounded-xl border bg-card/70 backdrop-blur-xl p-3.5 text-card-foreground shadow-xl transition-all duration-200 hover:border-primary/50">
        <SectionLabel icon="shield" text="Pi-hole DNS" />
        <div class="mb-2 grid grid-cols-2 gap-2">
            <div
                class="rounded-lg border border-border/10 bg-muted p-2 text-center"
            >
                <div class="font-mono text-sm font-bold text-foreground">
                    {{ dashboardData.pihole.queries }}
                </div>
                <div class="text-[11px] text-muted-foreground">Queries</div>
            </div>
            <div
                class="rounded-lg border border-destructive/15 bg-destructive/5 p-2 text-center"
            >
                <div class="font-mono text-sm font-bold text-destructive">
                    {{ dashboardData.pihole.blockedPercent }}%
                </div>
                <div class="text-[11px] text-muted-foreground">Blocked</div>
            </div>
        </div>
        <div class="h-1.5 overflow-hidden rounded-full bg-muted">
            <div
                class="h-full rounded-full transition-all duration-1000 ease-out bg-gradient-to-r from-destructive to-chart-4"
                :style="{
                    width: dashboardData.pihole.blockedPercent + '%',
                }"
            ></div>
        </div>
        <div class="mt-1 font-mono text-[12px] text-muted-foreground">
            {{ dashboardData.pihole.blockedToday }} blocked today
        </div>
    </div>
</template>
