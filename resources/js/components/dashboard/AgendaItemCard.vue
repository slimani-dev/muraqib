<script setup lang="ts">
import type { AgendaEvent } from '@/stores/useAgendaStore';

defineProps<{
    item: AgendaEvent;
    compact?: boolean;
}>();
</script>

<template>
    <div 
        class="flex gap-3 items-start border rounded-lg p-3 bg-card hover:bg-muted/30 transition-colors shadow-sm"
        :title="item.title"
    >
        <img v-if="item.poster" :src="item.poster" :class="compact ? 'w-10 h-14' : 'w-14 h-20'" class="object-cover rounded shadow-sm shrink-0" />
        <div v-else :class="compact ? 'w-10 h-14' : 'w-14 h-20'" class="bg-muted rounded flex items-center justify-center shrink-0">
            <i data-lucide="image" :class="compact ? 'w-4 h-4' : 'w-6 h-6'" class="text-muted-foreground/50"></i>
        </div>
        
        <div class="flex flex-col flex-1 min-w-0 py-0.5">
            <div class="flex items-center gap-2 mb-1">
                <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded" 
                      :class="item.type === 'movie' ? 'bg-primary/20 text-primary' : (item.type === 'series' ? 'bg-chart-4/20 text-chart-4' : 'bg-chart-2/20 text-chart-2')">
                    {{ item.type }}
                </span>
                <span v-if="item.hasFile" class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-emerald-500/20 text-emerald-500">
                    Downloaded
                </span>
            </div>
            
            <h5 class="font-bold text-[13px] text-foreground truncate leading-tight">{{ item.title }}</h5>
            
            <span v-if="item.episodeInfo" class="text-[11px] text-muted-foreground mt-0.5 truncate leading-tight block">
                {{ item.episodeInfo }}
            </span>
        </div>
    </div>
</template>
