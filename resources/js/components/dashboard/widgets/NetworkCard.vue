<script setup lang="ts">
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
</script>

<template>
    <Card class="flex flex-col h-full">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-cyan-400">router</span>
                <CardTitle>Network</CardTitle>
            </div>
            <Badge variant="secondary"
                class="bg-emerald-500/10 text-emerald-400 border-emerald-500/20 text-[10px] font-bold uppercase tracking-wide hover:bg-emerald-500/20">
                Tunnel: Healthy
            </Badge>
        </CardHeader>
        <CardContent class="flex-1 flex flex-col justify-end gap-2">
            <div class="flex justify-between items-end mb-2">
                <div>
                    <p class="text-xs text-muted-foreground uppercase font-medium">Throughput</p>
                    <p class="text-2xl font-mono font-bold text-card-foreground">450 <span
                            class="text-sm text-muted-foreground font-normal">Mbps</span></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-muted-foreground uppercase font-medium">Up / Down</p>
                    <p class="text-sm font-mono text-muted-foreground">120 / 330 Mbps</p>
                </div>
            </div>
            <!-- Complex Sparkline SVG -->
            <div class="h-24 w-full relative overflow-hidden rounded-lg bg-secondary/30">
                <svg class="absolute inset-0 w-full h-full" preserveAspectRatio="none" viewBox="0 0 100 50">
                    <defs>
                        <linearGradient id="networkGradient" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#6764f2" stop-opacity="0.4"></stop>
                            <stop offset="100%" stop-color="#6764f2" stop-opacity="0"></stop>
                        </linearGradient>
                    </defs>
                    <path d="M0 40 Q 10 35, 20 38 T 40 20 T 60 25 T 80 10 T 100 30 V 50 H 0 Z"
                        fill="url(#networkGradient)"></path>
                    <path d="M0 40 Q 10 35, 20 38 T 40 20 T 60 25 T 80 10 T 100 30" fill="none" stroke="#6764f2"
                        stroke-width="1.5" vector-effect="non-scaling-stroke"></path>
                </svg>
            </div>
        </CardContent>
    </Card>
</template>
