<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { computed } from 'vue';
import RepoEmptyState from './repos/RepoEmptyState.vue';
import RepoLogo from './repos/RepoLogo.vue';
import type { RepositorySummary } from './repos/repositories';
import { age, ciAppearance, formatCount } from './repos/repositories';
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

/** Repos marked "Managed by me" in the admin panel. */
const managed = computed(() =>
    props.repositories.filter((repository) => repository.is_managed),
);

const totals = computed(() => ({
    stars: managed.value.reduce((total, repo) => total + (repo.stars ?? 0), 0),
    downloads: managed.value.reduce(
        (total, repo) => total + (repo.downloads_total ?? 0),
        0,
    ),
    pullRequests: managed.value.reduce(
        (total, repo) => total + (repo.open_pull_requests ?? 0),
        0,
    ),
    issues: managed.value.reduce(
        (total, repo) => total + (repo.open_issues ?? 0),
        0,
    ),
}));
</script>

<template>
    <WidgetShell :mode="mode">
        <SectionLabel icon="package" text="Managed Repositories" />
        <div
            v-if="managed.length"
            class="-mt-1 mb-2 flex flex-wrap gap-x-3 gap-y-1 font-mono text-[11px] text-muted-foreground"
        >
            <span>{{ managed.length }} repos</span>
            <span class="flex items-center gap-1 text-amber-400"
                ><Icon icon="lucide:star" class="h-3 w-3" />{{
                    formatCount(totals.stars)
                }}</span
            >
            <span class="flex items-center gap-1 text-chart-1"
                ><Icon icon="lucide:download" class="h-3 w-3" />{{
                    formatCount(totals.downloads)
                }}</span
            >
            <span class="flex items-center gap-1 text-violet-400"
                ><Icon icon="lucide:git-pull-request" class="h-3 w-3" />{{
                    formatCount(totals.pullRequests)
                }}</span
            >
            <span class="flex items-center gap-1 text-rose-400"
                ><Icon icon="lucide:circle-dot" class="h-3 w-3" />{{
                    formatCount(totals.issues)
                }}</span
            >
        </div>
        <div
            v-if="!managed.length"
            class="rounded-xl border bg-card/70 backdrop-blur-xl"
        >
            <RepoEmptyState
                text="Mark repositories as managed to see them here."
            />
        </div>
        <div
            v-else
            class="grid grid-cols-1 divide-y divide-border/50 overflow-hidden rounded-xl border bg-card/70 backdrop-blur-xl widget-md:grid-cols-2 widget-md:gap-2 widget-md:divide-y-0 widget-md:overflow-visible widget-md:rounded-none widget-md:border-0 widget-md:bg-transparent widget-md:backdrop-blur-none widget-lg:grid-cols-3"
        >
            <a
                v-for="repo in managed"
                :key="repo.id"
                :href="repo.url"
                target="_blank"
                rel="noreferrer"
                class="flex cursor-pointer flex-col p-3 text-card-foreground transition-all duration-200 hover:bg-muted/40 widget-md:rounded-xl widget-md:border widget-md:bg-card/70 widget-md:p-3.5 widget-md:shadow-xl widget-md:backdrop-blur-xl widget-md:hover:-translate-y-0.5 widget-md:hover:border-primary/50 widget-md:hover:bg-card/70 widget-md:hover:shadow-2xl"
            >
                <div
                    class="mb-1 flex items-start justify-between gap-2 widget-md:mb-1.5"
                >
                    <div class="flex min-w-0 items-center gap-1.5">
                        <RepoLogo :repository="repo" size="h-4 w-4" />
                        <span
                            class="truncate text-[13px] font-bold text-foreground"
                            >{{ repo.title }}</span
                        >
                        <span
                            v-if="repo.ci"
                            class="h-1.5 w-1.5 shrink-0 rounded-full"
                            :class="ciAppearance[repo.ci.status].dot"
                            :title="`CI: ${ciAppearance[repo.ci.status].label}`"
                        ></span>
                    </div>
                    <span
                        v-if="repo.latest_release"
                        class="shrink-0 rounded border border-chart-3/20 bg-chart-3/10 px-1.5 py-0.5 font-mono text-[11px] text-chart-3"
                        :title="
                            repo.latest_release.published_at
                                ? `Released ${age(repo.latest_release.published_at)} ago`
                                : undefined
                        "
                    >
                        {{ repo.latest_release.tag }}
                    </span>
                </div>
                <!-- Grows so the stats line sits at the bottom of every card in a row -->
                <p
                    class="mb-1.5 line-clamp-1 flex-1 text-[12px] leading-relaxed text-muted-foreground widget-md:mb-2 widget-md:line-clamp-2"
                >
                    {{ repo.description || repo.full_name }}
                </p>
                <div
                    class="flex flex-wrap gap-x-3 gap-y-1 font-mono text-[12px]"
                >
                    <span
                        v-if="repo.stars !== null"
                        class="flex items-center gap-1 text-amber-400"
                        :title="
                            repo.stars_week_change
                                ? `${repo.stars_week_change > 0 ? '+' : ''}${repo.stars_week_change} this week`
                                : undefined
                        "
                    >
                        <Icon icon="lucide:star" class="h-3 w-3" />{{
                            formatCount(repo.stars)
                        }}
                        <span
                            v-if="repo.stars_week_change"
                            class="text-[10px]"
                            :class="
                                repo.stars_week_change > 0
                                    ? 'text-chart-3'
                                    : 'text-destructive'
                            "
                            >{{ repo.stars_week_change > 0 ? '+' : ''
                            }}{{ repo.stars_week_change }}</span
                        >
                    </span>
                    <span
                        v-if="repo.downloads_total !== null"
                        class="flex items-center gap-1 text-chart-1"
                        :title="
                            repo.downloads_monthly !== null
                                ? `${formatCount(repo.downloads_monthly)} this month`
                                : undefined
                        "
                    >
                        <Icon icon="lucide:download" class="h-3 w-3" />{{
                            formatCount(repo.downloads_total)
                        }}
                        <span
                            v-if="repo.downloads_monthly"
                            class="text-[10px] text-muted-foreground"
                            >{{ formatCount(repo.downloads_monthly) }}/mo</span
                        >
                    </span>
                    <span class="flex items-center gap-1 text-muted-foreground"
                        ><Icon icon="lucide:git-fork" class="h-3 w-3" />{{
                            formatCount(repo.forks)
                        }}</span
                    >
                    <span class="flex items-center gap-1 text-rose-400"
                        ><Icon icon="lucide:circle-dot" class="h-3 w-3" />{{
                            formatCount(repo.open_issues)
                        }}</span
                    >
                    <span
                        class="ml-auto flex items-center gap-1 text-violet-400"
                        ><Icon
                            icon="lucide:git-pull-request"
                            class="h-3 w-3"
                        />{{ formatCount(repo.open_pull_requests) }}</span
                    >
                </div>
            </a>
        </div>
    </WidgetShell>
</template>
