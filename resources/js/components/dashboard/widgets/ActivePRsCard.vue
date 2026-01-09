<script setup lang="ts">
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
</script>

<template>
    <Card class="flex flex-col h-full">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-violet-400">code_blocks</span>
                <CardTitle>Active PRs</CardTitle>
            </div>
            <Badge variant="secondary"
                class="bg-violet-500/20 text-violet-500 border-none text-xs px-2 py-0.5 rounded-full font-mono hover:bg-violet-500/30">
                3 Open
            </Badge>
        </CardHeader>
        <CardContent class="flex flex-col gap-3">
            <div
                class="flex items-start gap-3 p-3 rounded-lg bg-muted/50 hover:bg-muted transition-colors border border-transparent hover:border-border cursor-pointer">
                <span class="material-symbols-outlined text-purple-400 text-lg mt-0.5">merge_type</span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-mono text-card-foreground truncate"><span
                                class="text-purple-400">feat:</span>
                            auth-refactor</p>
                        <span class="text-[10px] text-muted-foreground font-mono">2h ago</span>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1 truncate">homelab-infra • #142</p>
                </div>
            </div>
            <div
                class="flex items-start gap-3 p-3 rounded-lg bg-muted/50 hover:bg-muted transition-colors border border-transparent hover:border-border cursor-pointer">
                <span class="material-symbols-outlined text-yellow-400 text-lg mt-0.5">bug_report</span>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-mono text-card-foreground truncate"><span
                                class="text-yellow-400">fix:</span>
                            docker-compose network</p>
                        <span class="text-[10px] text-muted-foreground font-mono">5h ago</span>
                    </div>
                    <p class="text-xs text-muted-foreground mt-1 truncate">media-stack • #89</p>
                </div>
            </div>
            <div class="flex items-center justify-between pt-1">
                <div class="flex items-center gap-2 text-xs text-muted-foreground">
                    <span class="material-symbols-outlined text-sm">history</span>
                    Last commit <span class="font-mono text-muted-foreground">a1b2c3d</span>
                </div>
                <Button variant="link" size="sm" class="text-xs text-primary h-auto p-0">
                    View Repo
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
