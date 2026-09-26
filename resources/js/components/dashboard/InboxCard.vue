<script setup lang="ts">

import { dashboardData } from './data';
import SectionLabel from './SectionLabel.vue';
</script>

<template>
    <div class="border bg-card/70 backdrop-blur-xl shadow-xl transition-all duration-200 hover:border-primary/50 text-card-foreground flex h-full flex-col rounded-xl p-3.5">
        <SectionLabel icon="mail" text="Inboxes" />
        <div class="space-y-2">
            <div
                v-for="inbox in dashboardData.inboxes"
                :key="inbox.name"
                class="flex items-center justify-between"
            >
                <span
                    class="flex items-center gap-1.5 text-[13px] text-slate-300"
                >
                    <i
                        :data-lucide="inbox.icon"
                        class="h-3 w-3 shrink-0"
                        :class="inbox.iconColor"
                    ></i>
                    {{ inbox.name }}
                </span>
                <span
                    class="rounded-full px-1.5 py-0.5 text-[12px] font-bold"
                    :style="{
                        background: inbox.bg,
                        color: inbox.color,
                        border: '1px solid ' + inbox.border,
                    }"
                >
                    {{ inbox.unread }}
                </span>
            </div>
        </div>
    </div>
</template>
