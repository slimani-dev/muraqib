<script setup lang="ts">
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
</script>

<template>
    <Card class="h-full">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-6">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-orange-500">dns</span>
                <CardTitle>Proxmox Cluster</CardTitle>
            </div>
            <div class="flex gap-4 text-xs font-mono text-muted-foreground">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> 3
                    Nodes</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-blue-500"></span> 12
                    LXC</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-purple-500"></span> 4
                    VMs</span>
            </div>
        </CardHeader>
        <CardContent>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-8">
                <!-- Donut 1: CPU -->
                <div class="flex flex-col items-center gap-3 relative group cursor-pointer">
                    <div class="relative w-28 h-28">
                        <svg class="w-full h-full transform -rotate-90">
                            <circle class="text-secondary" cx="56" cy="56" fill="transparent" r="48"
                                stroke="currentColor" stroke-width="8"></circle>
                            <circle
                                class="text-primary transition-all duration-1000 ease-out group-hover:text-primary-dark"
                                cx="56" cy="56" fill="transparent" r="48" stroke="currentColor"
                                stroke-dasharray="301.59" stroke-dashoffset="260" stroke-width="8"></circle>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-xl font-bold font-mono">14%</span>
                            <span class="text-[10px] text-muted-foreground uppercase">CPU</span>
                        </div>
                    </div>
                    <div class="text-xs text-center text-muted-foreground">Avg Load: 1.24</div>
                </div>
                <!-- Donut 2: RAM -->
                <div class="flex flex-col items-center gap-3 relative group cursor-pointer">
                    <div class="relative w-28 h-28">
                        <svg class="w-full h-full transform -rotate-90">
                            <circle class="text-secondary" cx="56" cy="56" fill="transparent" r="48"
                                stroke="currentColor" stroke-width="8"></circle>
                            <circle
                                class="text-blue-500 transition-all duration-1000 ease-out group-hover:text-blue-600"
                                cx="56" cy="56" fill="transparent" r="48" stroke="currentColor"
                                stroke-dasharray="301.59" stroke-dashoffset="120" stroke-width="8"></circle>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-xl font-bold font-mono">60%</span>
                            <span class="text-[10px] text-muted-foreground uppercase">RAM</span>
                        </div>
                    </div>
                    <div class="text-xs text-center text-muted-foreground">19GB / 32GB</div>
                </div>
                <!-- Donut 3: Storage -->
                <div class="flex flex-col items-center gap-3 relative group cursor-pointer">
                    <div class="relative w-28 h-28">
                        <svg class="w-full h-full transform -rotate-90">
                            <circle class="text-secondary" cx="56" cy="56" fill="transparent" r="48"
                                stroke="currentColor" stroke-width="8"></circle>
                            <circle
                                class="text-purple-500 transition-all duration-1000 ease-out group-hover:text-purple-600"
                                cx="56" cy="56" fill="transparent" r="48" stroke="currentColor"
                                stroke-dasharray="301.59" stroke-dashoffset="60" stroke-width="8"></circle>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-xl font-bold font-mono">80%</span>
                            <span class="text-[10px] text-muted-foreground uppercase">ZFS</span>
                        </div>
                    </div>
                    <div class="text-xs text-center text-muted-foreground">12TB / 15TB</div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
