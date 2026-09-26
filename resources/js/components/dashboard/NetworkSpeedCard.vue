<script setup lang="ts">

import { dashboardData } from './data';
import SectionLabel from './SectionLabel.vue';
</script>

<template>
    <div class="flex h-full flex-col rounded-xl border bg-card/70 backdrop-blur-xl p-3.5 text-card-foreground shadow-xl transition-all duration-200 hover:border-primary/50">
        <SectionLabel icon="activity" text="Network" />
        <div class="space-y-2.5">
            <div>
                <div class="mb-1 flex justify-between text-[12px]">
                    <span class="flex items-center gap-1 text-primary"
                        ><i data-lucide="arrow-down" class="h-2.5 w-2.5"></i>
                        Down</span
                    >
                    <span class="font-mono font-bold">{{
                        dashboardData.network.down
                    }}</span>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full bg-primary transition-all duration-1000 ease-out"
                        :style="{
                            width: dashboardData.network.downPercent + '%',
                        }"
                    ></div>
                </div>
            </div>
            <div>
                <div class="mb-1 flex justify-between text-[12px]">
                    <span class="flex items-center gap-1 text-chart-1"
                        ><i data-lucide="arrow-up" class="h-2.5 w-2.5"></i>
                        Up</span
                    >
                    <span class="font-mono font-bold">{{
                        dashboardData.network.up
                    }}</span>
                </div>
                <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full bg-chart-1 transition-all duration-1000 ease-out"
                        :style="{
                            width: dashboardData.network.upPercent + '%',
                        }"
                    ></div>
                </div>
            </div>
            <div
                class="flex justify-between border-t border-border/10 pt-1.5 font-mono text-[12px] text-muted-foreground"
            >
                <span
                    >Ping
                    <span class="text-chart-3">{{
                        dashboardData.network.ping
                    }}</span></span
                >
                <span
                    >ISP
                    <span class="text-foreground">{{
                        dashboardData.network.isp
                    }}</span></span
                >
            </div>
        </div>
    </div>
</template>
