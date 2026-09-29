<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { computed } from 'vue';
import Sparkline from '../ui/Sparkline.vue';
import RepoEmptyState from './repos/RepoEmptyState.vue';
import type { RepositorySummary, RepositoryTrends } from './repos/repositories';
import { formatCount } from './repos/repositories';
import SectionLabel from './SectionLabel.vue';
import type { WidgetMode } from './widgets/widgetMode';
import WidgetShell from './widgets/WidgetShell.vue';

const props = withDefaults(
    defineProps<{
        repositories?: RepositorySummary[];
        trends?: RepositoryTrends | null;
        mode?: WidgetMode;
    }>(),
    {
        repositories: () => [],
        trends: null,
        mode: 'desktop',
    },
);

const totalStars = computed(() =>
    props.repositories.reduce((total, repo) => total + (repo.stars ?? 0), 0),
);
const starsThisWeek = computed(() =>
    props.repositories.reduce(
        (total, repo) => total + (repo.stars_week_change ?? 0),
        0,
    ),
);
const monthlyDownloads = computed(() =>
    props.repositories.reduce(
        (total, repo) => total + (repo.downloads_monthly ?? 0),
        0,
    ),
);
const hasDownloads = computed(() =>
    props.repositories.some((repo) => repo.downloads_monthly !== null),
);

/** The repo that gained the most stars this week. */
const rising = computed(
    () =>
        [...props.repositories]
            .filter((repo) => (repo.stars_week_change ?? 0) > 0)
            .sort(
                (a, b) =>
                    (b.stars_week_change ?? 0) - (a.stars_week_change ?? 0),
            )[0] ?? null,
);

const counters = computed(() => [
    {
        label: 'Stars',
        icon: 'lucide:star',
        value: formatCount(totalStars.value),
        change: starsThisWeek.value,
        changeLabel: 'this week',
        chart: props.trends?.stars ?? [],
        color: 'var(--chart-4)',
        tone: 'text-chart-4',
    },
    ...(hasDownloads.value
        ? [
              {
                  label: 'Downloads / month',
                  icon: 'lucide:download',
                  value: formatCount(monthlyDownloads.value),
                  change: null,
                  changeLabel: '',
                  chart: props.trends?.downloads ?? [],
                  color: 'var(--chart-1)',
                  tone: 'text-chart-1',
              },
          ]
        : []),
]);
</script>

<template>
    <WidgetShell :mode="mode">
        <div
            class="flex h-full flex-col rounded-xl border bg-card/70 p-3.5 text-card-foreground shadow-xl backdrop-blur-xl transition-all duration-200 hover:border-primary/50"
        >
            <SectionLabel icon="trending-up" text="Stars & Downloads" />
            <RepoEmptyState
                v-if="!repositories.length"
                text="Add repositories to count their stars and downloads."
            />
            <div v-else class="grid grid-cols-1 gap-2 widget-md:grid-cols-2">
                <div
                    v-for="counter in counters"
                    :key="counter.label"
                    class="relative overflow-hidden rounded-lg border border-border/50 bg-background/40 p-3"
                >
                    <div class="relative z-10">
                        <div
                            class="flex items-center gap-1.5 text-[11px] text-muted-foreground"
                        >
                            <Icon
                                :icon="counter.icon"
                                class="h-3 w-3"
                                :class="counter.tone"
                            />
                            {{ counter.label }}
                        </div>
                        <div class="mt-0.5 flex items-baseline gap-2">
                            <span
                                class="font-mono text-2xl font-bold text-foreground"
                                >{{ counter.value }}</span
                            >
                            <span
                                v-if="counter.change"
                                class="font-mono text-[11px]"
                                :class="
                                    counter.change > 0
                                        ? 'text-chart-3'
                                        : 'text-destructive'
                                "
                            >
                                {{ counter.change > 0 ? '+' : ''
                                }}{{ counter.change }} {{ counter.changeLabel }}
                            </span>
                        </div>
                    </div>
                    <div
                        v-if="counter.chart.length > 1"
                        class="absolute inset-x-0 bottom-0 h-1/2 opacity-60"
                    >
                        <Sparkline
                            :data="counter.chart"
                            :color="counter.color"
                            :stroke-width="1.2"
                            :formatter="(v: number) => formatCount(v)"
                        />
                    </div>
                </div>
            </div>
            <p
                v-if="rising"
                class="mt-2 truncate text-[11px] text-muted-foreground"
            >
                Rising:
                <span class="font-semibold text-foreground">{{
                    rising.title
                }}</span>
                <span class="text-chart-3">
                    +{{ rising.stars_week_change }}</span
                >
                stars this week
            </p>
        </div>
    </WidgetShell>
</template>
