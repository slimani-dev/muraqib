<script setup lang="ts">
import { computed } from 'vue';
import RepoEmptyState from './repos/RepoEmptyState.vue';
import RepoLogo from './repos/RepoLogo.vue';
import type { RepositorySummary } from './repos/repositories';
import { age } from './repos/repositories';
import SectionLabel from './SectionLabel.vue';
import type { WidgetMode } from './widgets/widgetMode';
import WidgetShell from './widgets/WidgetShell.vue';

const props = withDefaults(defineProps<{ repositories?: RepositorySummary[]; mode?: WidgetMode }>(), {
    repositories: () => [],
    mode: 'desktop',
});

/** The latest release of each repo, newest first. */
const releases = computed(() => props.repositories
    .filter((repository) => repository.latest_release)
    .map((repository) => ({ repository, release: repository.latest_release! }))
    .sort((a, b) => (b.release.published_at ?? '').localeCompare(a.release.published_at ?? ''))
    .slice(0, 9));
</script>

<template>
    <WidgetShell :mode="mode">
        <div class="rounded-xl border bg-card/70 p-3.5 text-card-foreground shadow-xl backdrop-blur-xl">
            <SectionLabel icon="tag" text="Latest Releases" />
            <RepoEmptyState v-if="!releases.length" text="No releases yet." />
            <div v-else class="space-y-0.5 widget-md:grid widget-md:grid-cols-2 widget-lg:grid-cols-3 widget-md:gap-x-4 widget-md:gap-y-1 widget-md:space-y-0">
                <a v-for="{ repository, release } in releases" :key="repository.id" :href="release.url ?? repository.url" target="_blank" rel="noreferrer"
                    class="flex items-center justify-between gap-2 rounded-md p-1.5 text-[12px] transition-colors hover:bg-muted/50">
                    <span class="flex min-w-0 items-center gap-1.5">
                        <RepoLogo :repository="repository" size="h-4 w-4" />
                        <span class="truncate font-semibold text-foreground/90">{{ repository.title }}</span>
                    </span>
                    <span class="flex shrink-0 items-center gap-2">
                        <span class="rounded border border-chart-3/20 bg-chart-3/10 px-1.5 py-0.5 font-mono text-[10px] text-chart-3">{{ release.tag }}</span>
                        <span class="font-mono text-[11px] text-muted-foreground">{{ age(release.published_at) }}</span>
                    </span>
                </a>
            </div>
        </div>
    </WidgetShell>
</template>
