<script setup lang="ts">

import { dashboardData } from './data';
import SectionLabel from './SectionLabel.vue';
</script>

<template>
    <div class="flex h-full flex-col rounded-xl border bg-card/70 backdrop-blur-xl p-3.5 text-card-foreground shadow-xl transition-all duration-200 hover:border-primary/50">
        <SectionLabel icon="github" text="Open Source" />
        <div class="grid grid-cols-2 gap-1.5">
            <div
                class="rounded-lg border border-chart-4/15 bg-chart-4/5 p-2 text-center"
            >
                <i
                    data-lucide="star"
                    class="mx-auto mb-0.5 h-3 w-3 text-chart-4"
                ></i>
                <div class="font-mono text-sm font-bold text-foreground">
                    {{ dashboardData.githubStats.stars }}
                </div>
                <div class="text-[11px] text-muted-foreground">Stars</div>
            </div>
            <div
                class="rounded-lg border border-chart-3/15 bg-chart-3/5 p-2 text-center"
            >
                <i
                    data-lucide="git-pull-request"
                    class="mx-auto mb-0.5 h-3 w-3 text-chart-3"
                ></i>
                <div class="font-mono text-sm font-bold text-chart-3">
                    {{ dashboardData.githubStats.prs }}
                </div>
                <div class="text-[11px] text-muted-foreground">Open PRs</div>
            </div>
            <div
                class="rounded-lg border border-destructive/15 bg-destructive/5 p-2 text-center"
            >
                <i
                    data-lucide="circle-dot"
                    class="mx-auto mb-0.5 h-3 w-3 text-destructive"
                ></i>
                <div class="font-mono text-sm font-bold text-destructive">
                    {{ dashboardData.githubStats.issues }}
                </div>
                <div class="text-[11px] text-muted-foreground">Issues</div>
            </div>
            <div
                class="rounded-lg border border-chart-1/15 bg-chart-1/5 p-2 text-center"
            >
                <i
                    data-lucide="download"
                    class="mx-auto mb-0.5 h-3 w-3 text-chart-1"
                ></i>
                <div class="font-mono text-sm font-bold text-chart-1">
                    {{ dashboardData.githubStats.downloads }}
                </div>
                <div class="text-[11px] text-muted-foreground">Downloads</div>
            </div>
        </div>
    </div>
</template>
