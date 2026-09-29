<script setup lang="ts">
import { Icon } from '@iconify/vue';
import type { AgendaEvent } from '@/stores/useAgendaStore';

defineProps<{
    item: AgendaEvent;
    compact?: boolean;
}>();
</script>

<template>
    <div
        class="flex items-start gap-3 rounded-lg border bg-card p-3 shadow-sm transition-colors hover:bg-muted/30"
        :title="item.title"
    >
        <img
            v-if="item.poster"
            :src="item.poster"
            :class="compact ? 'h-14 w-10' : 'h-20 w-14'"
            class="shrink-0 rounded object-cover shadow-sm"
        />
        <div
            v-else
            :class="compact ? 'h-14 w-10' : 'h-20 w-14'"
            class="flex shrink-0 items-center justify-center rounded bg-muted"
        >
            <Icon
                icon="lucide:image"
                :class="compact ? 'h-4 w-4' : 'h-6 w-6'"
                class="text-muted-foreground/50"
            />
        </div>

        <div class="flex min-w-0 flex-1 flex-col py-0.5">
            <div class="mb-1 flex items-center gap-2">
                <span
                    class="rounded px-1.5 py-0.5 text-[9px] font-bold tracking-wider uppercase"
                    :class="
                        item.type === 'movie'
                            ? 'bg-primary/20 text-primary'
                            : item.type === 'series'
                              ? 'bg-chart-4/20 text-chart-4'
                              : 'bg-chart-2/20 text-chart-2'
                    "
                >
                    {{ item.type }}
                </span>
                <span
                    v-if="item.hasFile"
                    class="rounded bg-emerald-500/20 px-1.5 py-0.5 text-[9px] font-bold tracking-wider text-emerald-500 uppercase"
                >
                    Downloaded
                </span>
            </div>

            <h5
                class="truncate text-[13px] leading-tight font-bold text-foreground"
            >
                {{ item.title }}
            </h5>

            <span
                v-if="item.episodeInfo"
                class="mt-0.5 block truncate text-[11px] leading-tight text-muted-foreground"
            >
                {{ item.episodeInfo }}
            </span>
        </div>
    </div>
</template>
