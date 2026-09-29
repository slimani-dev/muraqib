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

const props = withDefaults(
    defineProps<{ repositories?: RepositorySummary[]; mode?: WidgetMode }>(),
    {
        repositories: () => [],
        mode: 'desktop',
    },
);

/** Newest open issues across every repo. */
const issues = computed(() =>
    props.repositories
        .flatMap((repository) =>
            repository.issues.map((issue) => ({ ...issue, repository })),
        )
        .sort((a, b) => (b.created_at ?? '').localeCompare(a.created_at ?? ''))
        .slice(0, 12),
);
</script>

<template>
    <WidgetShell :mode="mode">
        <div
            class="rounded-xl border bg-card/70 p-3.5 text-card-foreground shadow-xl backdrop-blur-xl"
        >
            <SectionLabel icon="circle-dot" text="Recent Issues" />
            <RepoEmptyState v-if="!issues.length" text="No open issues." />
            <div
                v-else
                class="space-y-0.5 widget-md:grid widget-md:grid-cols-2 widget-md:space-y-0 widget-md:gap-x-4 widget-md:gap-y-1 widget-lg:grid-cols-3"
            >
                <a
                    v-for="issue in issues"
                    :key="`${issue.repository.id}-${issue.number}`"
                    :href="issue.url ?? issue.repository.url"
                    target="_blank"
                    rel="noreferrer"
                    class="flex items-center justify-between gap-2 rounded-md p-1.5 transition-colors hover:bg-muted/50"
                >
                    <span class="flex min-w-0 items-center gap-1.5 text-[13px]">
                        <RepoLogo
                            :repository="issue.repository"
                            size="h-3.5 w-3.5"
                        />
                        <span class="flex min-w-0 flex-col">
                            <span class="truncate text-foreground/90">{{
                                issue.title
                            }}</span>
                            <span
                                class="truncate text-[10px] text-muted-foreground"
                                >{{ issue.repository.title }} #{{ issue.number
                                }}<template v-if="issue.author">
                                    · {{ issue.author }}</template
                                ></span
                            >
                        </span>
                    </span>
                    <span
                        class="flex shrink-0 items-center gap-2 font-mono text-[11px] text-muted-foreground"
                    >
                        <span
                            v-if="issue.comments"
                            class="flex items-center gap-0.5"
                            ><Icon
                                icon="lucide:message-square"
                                class="h-3 w-3"
                            />{{ issue.comments }}</span
                        >
                        {{ age(issue.created_at) }}
                    </span>
                </a>
            </div>
        </div>
    </WidgetShell>
</template>
