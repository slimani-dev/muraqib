<script setup lang="ts">
import { dashboardData } from './data';
</script>

<template>
    <div
        class="flex h-full flex-col gap-1.5 rounded-xl border bg-card/70 p-3.5 text-card-foreground shadow-xl backdrop-blur-xl transition-all duration-200 hover:border-primary/50"
    >
        <div class="mb-0.5 text-base font-bold text-white">
            {{ dashboardData.greeting.timeOfDay }} 👋
        </div>
        <div class="font-mono text-[10px] text-slate-500" id="date-display">
            {{
                new Date().toLocaleDateString('en-GB', {
                    weekday: 'long',
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                })
            }}
        </div>
        <div class="mt-auto flex gap-2 border-t border-border/10 pt-2">
            <span
                class="b-crit flex cursor-pointer items-center gap-1 rounded-full px-2 py-0.5 text-[9px] font-bold"
            >
                <i data-lucide="alert-triangle" class="h-2.5 w-2.5"></i>
                {{ dashboardData.greeting.alerts }} Alert
            </span>
            <span
                class="b-warn flex cursor-pointer items-center gap-1 rounded-full px-2 py-0.5 text-[9px] font-semibold"
            >
                <i data-lucide="arrow-up-circle" class="h-2.5 w-2.5"></i>
                {{ dashboardData.greeting.updates }} Updates
            </span>
        </div>
    </div>
</template>
