<script setup lang="ts">
import { Button } from '@/components/ui/button';
</script>

<template>
    <div class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div class="flex flex-col gap-1">
            <h2 class="text-3xl font-bold tracking-tight text-foreground">
                Good Evening, Slimani
            </h2>
            <div class="flex items-center gap-2">
                <span class="relative flex h-2.5 w-2.5">
                    <span
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                </span>
                <span class="font-mono text-sm text-slate-400">System Status:
                    <span class="text-emerald-400">Healthy</span></span>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <Button variant="default" class="rounded-full">
                <span class="material-symbols-outlined text-[18px]">add</span>
                Deploy App
            </Button>
            <Button variant="outline" class="rounded-full">
                <span class="material-symbols-outlined animate-spin-slow text-[18px]">refresh</span>
                Refresh Stacks
            </Button>
            <Button variant="destructive" class="rounded-full">
                <span class="material-symbols-outlined text-[18px]">lock</span>
                Lock Down
            </Button>
        </div>
    </div>
</template>
