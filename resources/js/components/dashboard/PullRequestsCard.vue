<script setup lang="ts">
import { Icon } from '@iconify/vue';
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

/** Open, non-draft pull requests across every repo, the ones waiting longest first. */
const pullRequests = computed(() => props.repositories
    .flatMap((repository) => repository.pull_requests
        .filter((pr) => !pr.draft)
        .map((pr) => ({ ...pr, repository })))
    .sort((a, b) => (a.created_at ?? '').localeCompare(b.created_at ?? ''))
    .slice(0, 12));
</script>

<template>
    <WidgetShell :mode="mode">
        <div class="rounded-xl border bg-card/70 p-3.5 text-card-foreground shadow-xl backdrop-blur-xl">
            <SectionLabel icon="inbox" text="Awaiting Review" />
            <RepoEmptyState v-if="!pullRequests.length" :text="repositories.length ? 'No open pull requests. Nice.' : 'Add repositories to track their pull requests.'" />
            <div v-else class="space-y-0.5 widget-md:grid widget-md:grid-cols-2 widget-lg:grid-cols-3 widget-md:gap-x-4 widget-md:gap-y-1 widget-md:space-y-0">
                <a v-for="pr in pullRequests" :key="`${pr.repository.id}-${pr.number}`" :href="pr.url ?? pr.repository.url" target="_blank" rel="noreferrer"
                    class="flex items-center justify-between gap-2 rounded-md p-1.5 transition-colors hover:bg-muted/50 widget-md:border widget-md:border-border/40 widget-md:p-2.5">
                    <span class="flex min-w-0 items-center gap-1.5 text-[13px]">
                        <RepoLogo :repository="pr.repository" size="h-3.5 w-3.5" />
                        <span class="flex min-w-0 flex-col">
                            <span class="truncate text-foreground/90">{{ pr.title }}</span>
                            <span class="truncate text-[10px] text-muted-foreground">{{ pr.repository.title }} #{{ pr.number }}<template v-if="pr.author"> · {{ pr.author }}</template></span>
                        </span>
                    </span>
                    <span class="flex shrink-0 items-center gap-2">
                        <span v-if="pr.reviewers" class="flex items-center gap-0.5 text-[10px] text-chart-4" :title="`${pr.reviewers} review(s) requested`">
                            <Icon icon="lucide:eye" class="h-3 w-3" />{{ pr.reviewers }}
                        </span>
                        <span class="font-mono text-[11px] text-muted-foreground">{{ age(pr.created_at) }}</span>
                    </span>
                </a>
            </div>
        </div>
    </WidgetShell>
</template>
