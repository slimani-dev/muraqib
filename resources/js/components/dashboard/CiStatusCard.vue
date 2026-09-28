<script setup lang="ts">
import { computed } from 'vue';
import RepoEmptyState from './repos/RepoEmptyState.vue';
import RepoLogo from './repos/RepoLogo.vue';
import type { RepositorySummary } from './repos/repositories';
import { age, ciAppearance } from './repos/repositories';
import SectionLabel from './SectionLabel.vue';
import type { WidgetMode } from './widgets/widgetMode';
import WidgetShell from './widgets/WidgetShell.vue';

const props = withDefaults(defineProps<{ repositories?: RepositorySummary[]; mode?: WidgetMode }>(), {
    repositories: () => [],
    mode: 'desktop',
});

const order: Record<string, number> = { failure: 0, running: 1, cancelled: 2, unknown: 3, success: 4 };

/** Repos with CI, failing first. */
const builds = computed(() => props.repositories
    .filter((repository) => repository.ci)
    .sort((a, b) => order[a.ci!.status] - order[b.ci!.status]));

const failing = computed(() => builds.value.filter((repository) => repository.ci!.status === 'failure').length);
</script>

<template>
    <WidgetShell :mode="mode">
        <div class="rounded-xl border bg-card/70 p-3.5 text-card-foreground shadow-xl backdrop-blur-xl">
            <SectionLabel icon="workflow" text="CI Status" />
            <RepoEmptyState v-if="!builds.length" text="No CI runs found for your repositories." />
            <template v-else>
                <p class="mb-2 text-[11px]" :class="failing ? 'text-destructive' : 'text-chart-3'">
                    {{ failing ? `${failing} failing` : 'All builds passing' }} · {{ builds.length }} repos
                </p>
                <div class="flex flex-wrap gap-1.5">
                    <a v-for="repo in builds" :key="repo.id" :href="repo.ci!.url ?? repo.url" target="_blank" rel="noreferrer"
                        class="flex max-w-full items-center gap-1.5 rounded-full border border-border/50 bg-background/40 py-1 pr-2.5 pl-1.5 text-[11px] transition-colors hover:border-primary/50"
                        :title="`${ciAppearance[repo.ci!.status].label}${repo.ci!.finished_at ? ` · ${age(repo.ci!.finished_at)} ago` : ''}`">
                        <span class="h-2 w-2 shrink-0 rounded-full" :class="ciAppearance[repo.ci!.status].dot"></span>
                        <RepoLogo :repository="repo" size="h-3.5 w-3.5" />
                        <span class="truncate font-medium text-foreground/90">{{ repo.title }}</span>
                    </a>
                </div>
            </template>
        </div>
    </WidgetShell>
</template>
