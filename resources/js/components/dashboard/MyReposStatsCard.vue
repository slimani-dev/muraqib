<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { computed } from 'vue';
import RepoEmptyState from './repos/RepoEmptyState.vue';
import type { RepositorySummary } from './repos/repositories';
import { formatCount } from './repos/repositories';
import SectionLabel from './SectionLabel.vue';
import type { WidgetMode } from './widgets/widgetMode';
import WidgetShell from './widgets/WidgetShell.vue';

const props = withDefaults(defineProps<{ repositories?: RepositorySummary[]; mode?: WidgetMode }>(), {
    repositories: () => [],
    mode: 'desktop',
});

/** Totals across the repos marked "Managed by me". */
const managed = computed(() => props.repositories.filter((repository) => repository.is_managed));

const sum = (pick: (repository: RepositorySummary) => number | null) =>
    managed.value.reduce((total, repository) => total + (pick(repository) ?? 0), 0);

const stats = computed(() => [
    { label: 'Stars', icon: 'lucide:star', value: sum((r) => r.stars), tone: 'border-chart-4/15 bg-chart-4/5 text-chart-4', valueClass: 'text-foreground' },
    { label: 'Open PRs', icon: 'lucide:git-pull-request', value: sum((r) => r.open_pull_requests), tone: 'border-chart-3/15 bg-chart-3/5 text-chart-3', valueClass: 'text-chart-3' },
    { label: 'Issues', icon: 'lucide:circle-dot', value: sum((r) => r.open_issues), tone: 'border-destructive/15 bg-destructive/5 text-destructive', valueClass: 'text-destructive' },
    { label: 'Downloads', icon: 'lucide:download', value: sum((r) => r.downloads_total), tone: 'border-chart-1/15 bg-chart-1/5 text-chart-1', valueClass: 'text-chart-1' },
]);
</script>

<template>
    <WidgetShell :mode="mode">
        <div class="flex h-full flex-col rounded-xl border bg-card/70 p-3.5 text-card-foreground shadow-xl backdrop-blur-xl transition-all duration-200 hover:border-primary/50">
            <SectionLabel icon="package" :text="managed.length ? `My Repos · ${managed.length}` : 'My Repos'" />
            <RepoEmptyState v-if="!managed.length" text="Mark repositories as managed to see their totals." />
            <div v-else class="grid grid-cols-2 gap-1.5 widget-md:grid-cols-4 widget-md:gap-3">
                <div v-for="stat in stats" :key="stat.label"
                    class="rounded-lg border p-2 text-center widget-md:flex widget-md:items-center widget-md:gap-3 widget-md:p-3 widget-md:text-left"
                    :class="stat.tone">
                    <Icon :icon="stat.icon" class="mx-auto mb-0.5 h-3 w-3 widget-md:mx-0 widget-md:mb-0 widget-md:h-6 widget-md:w-6" />
                    <div>
                        <div class="font-mono text-sm font-bold widget-md:text-xl" :class="stat.valueClass">{{ formatCount(stat.value) }}</div>
                        <div class="text-[11px] text-muted-foreground">{{ stat.label }}</div>
                    </div>
                </div>
            </div>
        </div>
    </WidgetShell>
</template>
