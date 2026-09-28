<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { computed } from 'vue';
import RepoEmptyState from './repos/RepoEmptyState.vue';
import RepoLogo from './repos/RepoLogo.vue';
import type { RepositorySummary } from './repos/repositories';
import { formatCount } from './repos/repositories';
import SectionLabel from './SectionLabel.vue';
import type { WidgetMode } from './widgets/widgetMode';
import WidgetShell from './widgets/WidgetShell.vue';

const props = withDefaults(defineProps<{ repositories?: RepositorySummary[]; mode?: WidgetMode }>(), {
    repositories: () => [],
    mode: 'desktop',
});

/** Open-source projects you follow (not your own; those have the My Repos widget), most-starred first. */
const watched = computed(() => props.repositories
    .filter((repository) => !repository.is_managed)
    .sort((a, b) => (b.stars ?? -1) - (a.stars ?? -1)));
</script>

<template>
    <WidgetShell :mode="mode">
        <div class="flex h-full flex-col rounded-xl border bg-card/70 p-3.5 text-card-foreground shadow-xl backdrop-blur-xl transition-all duration-200 hover:border-primary/50">
            <SectionLabel icon="git-branch" text="Open Source" />
            <RepoEmptyState v-if="!watched.length" text="Add open-source repositories to watch (not marked as managed)." />
            <div v-else class="grid grid-cols-1 gap-0.5 widget-md:grid-cols-2 widget-lg:grid-cols-3 widget-md:gap-x-4">
                <a v-for="repo in watched" :key="repo.id" :href="repo.url" target="_blank" rel="noreferrer"
                    class="flex items-center justify-between gap-2 rounded-md p-1.5 text-[12px] transition-colors hover:bg-muted/50">
                    <span class="flex min-w-0 items-center gap-1.5">
                        <RepoLogo :repository="repo" size="h-4 w-4" />
                        <span class="truncate font-semibold text-foreground/90" :title="repo.full_name">{{ repo.title }}</span>
                    </span>
                    <span class="flex shrink-0 items-center gap-2.5 font-mono text-[11px]">
                        <span v-if="repo.stars !== null" class="flex items-center gap-0.5 text-amber-400"><Icon icon="lucide:star" class="h-2.5 w-2.5" />{{ formatCount(repo.stars) }}</span>
                        <span v-if="repo.downloads_total !== null" class="flex items-center gap-0.5 text-chart-1"><Icon icon="lucide:download" class="h-2.5 w-2.5" />{{ formatCount(repo.downloads_total) }}</span>
                    </span>
                </a>
            </div>
        </div>
    </WidgetShell>
</template>
