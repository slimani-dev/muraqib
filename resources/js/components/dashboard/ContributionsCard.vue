<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { useElementSize } from '@vueuse/core';
import { computed, ref, useTemplateRef } from 'vue';
import { formatCount } from './repos/repositories';
import SectionLabel from './SectionLabel.vue';
import type { WidgetMode } from './widgets/widgetMode';
import WidgetShell from './widgets/WidgetShell.vue';

type Day = { date: string; count: number; level: number };

/** One entry of the dashboard's `contributions` prop (App\Services\Git\RepositoryDashboard::contributions). */
export type AccountContributions = {
    id: number;
    name: string;
    username: string;
    profile_url: string;
    provider_icon: string;
    total: number;
    commits: number;
    pull_requests: number;
    reviews: number;
    issues: number;
    private: number;
    weeks: Day[][];
};

const props = withDefaults(defineProps<{ accounts?: AccountContributions[]; mode?: WidgetMode }>(), {
    accounts: () => [],
    mode: 'desktop',
});

const selectedId = ref<number | null>(null);
const account = computed(() => props.accounts.find((candidate) => candidate.id === selectedId.value) ?? props.accounts[0] ?? null);

/** Cell size and gap in px, matching the h-[11px] / gap-[3px] classes below. */
const CELL = 11;
const GAP = 3;
const DAY_LABELS_WIDTH = 28;

const grid = useTemplateRef<HTMLElement>('grid');
const { width } = useElementSize(grid);

/** As many of the most recent weeks as fit the widget's width (all 53 when there's room). */
const visibleWeeks = computed(() => {
    const weeks = account.value?.weeks ?? [];
    const fits = width.value ? Math.floor((width.value - DAY_LABELS_WIDTH + GAP) / (CELL + GAP)) : weeks.length;

    return weeks.slice(-Math.max(1, Math.min(weeks.length, fits)));
});

const weekday = (date: string): number => new Date(`${date}T00:00:00Z`).getUTCDay();

/** Seven slots per week (Sunday first); the first week of the year can start mid-week. */
const columns = computed(() => visibleWeeks.value.map((week) => {
    const slots: (Day | null)[] = Array.from({ length: 7 }, () => null);
    week.forEach((day) => {
        slots[weekday(day.date)] = day;
    });

    return slots;
}));

const monthLabels = computed(() => visibleWeeks.value.map((week, index) => {
    const month = new Date(`${week[0].date}T00:00:00Z`).getUTCMonth();
    const previous = index > 0 ? new Date(`${visibleWeeks.value[index - 1][0].date}T00:00:00Z`).getUTCMonth() : null;

    return month !== previous && index < visibleWeeks.value.length - 1
        ? new Date(Date.UTC(2000, month, 1)).toLocaleString(undefined, { month: 'short', timeZone: 'UTC' })
        : '';
}));

const levelClasses = ['bg-muted/70', 'bg-chart-3/30', 'bg-chart-3/55', 'bg-chart-3/80', 'bg-chart-3'];

const streaks = computed(() => {
    const today = new Date().toISOString().slice(0, 10);
    const days = (account.value?.weeks ?? []).flat().filter((day) => day.date <= today);
    let longest = 0;
    let run = 0;

    days.forEach((day) => {
        run = day.count > 0 ? run + 1 : 0;
        longest = Math.max(longest, run);
    });

    // Like GitHub: no contribution yet today doesn't break the current streak
    let current = 0;
    const fromEnd = [...days].reverse();
    const start = fromEnd[0]?.date === today && fromEnd[0].count === 0 ? 1 : 0;

    for (const day of fromEnd.slice(start)) {
        if (day.count === 0) {
            break;
        }

        current++;
    }

    return { current, longest };
});

const breakdown = computed(() => account.value ? [
    { label: 'Commits', icon: 'lucide:git-commit-horizontal', value: account.value.commits },
    { label: 'Pull requests', icon: 'lucide:git-pull-request', value: account.value.pull_requests },
    { label: 'Reviews', icon: 'lucide:eye', value: account.value.reviews },
    { label: 'Issues', icon: 'lucide:circle-dot', value: account.value.issues },
    { label: 'Current streak', icon: 'lucide:flame', value: streaks.value.current, suffix: 'd' },
    { label: 'Longest streak', icon: 'lucide:trophy', value: streaks.value.longest, suffix: 'd' },
] : []);

const describe = (day: Day): string => {
    const date = new Date(`${day.date}T00:00:00Z`).toLocaleDateString(undefined, { month: 'long', day: 'numeric', year: 'numeric', timeZone: 'UTC' });

    return `${day.count || 'No'} contribution${day.count === 1 ? '' : 's'} on ${date}`;
};
</script>

<template>
    <WidgetShell :mode="mode">
        <div class="flex h-full flex-col rounded-xl border bg-card/70 p-3.5 text-card-foreground shadow-xl backdrop-blur-xl transition-all duration-200 hover:border-primary/50">
            <div class="flex items-center justify-between gap-2">
                <SectionLabel icon="calendar-days" text="Contributions" />
                <div v-if="accounts.length > 1" class="-mt-2 flex gap-1">
                    <button v-for="candidate in accounts" :key="candidate.id" type="button"
                        class="cursor-pointer rounded-md px-2 py-0.5 text-[10px] font-semibold transition-colors"
                        :class="candidate.id === account?.id ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground'"
                        @click="selectedId = candidate.id">
                        {{ candidate.username }}
                    </button>
                </div>
            </div>

            <div v-if="!account" class="flex flex-col items-center gap-1.5 py-4 text-center">
                <Icon icon="simple-icons:github" class="h-5 w-5 text-muted-foreground/50" />
                <p class="text-xs text-muted-foreground">Add a GitHub account with a token to see your contribution calendar.</p>
                <a href="/admin/git-accounts" class="text-xs font-medium text-primary hover:underline">Manage Git accounts</a>
            </div>

            <template v-else>
                <a :href="account.profile_url" target="_blank" rel="noreferrer" class="mb-2 flex items-baseline gap-1.5 hover:underline">
                    <span class="font-mono text-lg font-bold text-foreground">{{ formatCount(account.total) }}</span>
                    <span class="text-[12px] text-muted-foreground">contributions in the last year · {{ account.username }}</span>
                </a>

                <div ref="grid" class="w-full overflow-hidden">
                    <!-- Month labels, one slot per visible week -->
                    <div class="mb-1 flex gap-[3px] text-[10px] text-muted-foreground" :style="{ paddingLeft: `${DAY_LABELS_WIDTH}px` }">
                        <span v-for="(label, index) in monthLabels" :key="index" class="w-[11px] shrink-0 overflow-visible whitespace-nowrap">{{ label }}</span>
                    </div>
                    <div class="flex">
                        <div class="flex shrink-0 flex-col gap-[3px] text-[10px] leading-[11px] text-muted-foreground" :style="{ width: `${DAY_LABELS_WIDTH}px` }">
                            <span v-for="(label, row) in ['', 'Mon', '', 'Wed', '', 'Fri', '']" :key="row" class="h-[11px]">{{ label }}</span>
                        </div>
                        <div class="flex gap-[3px]">
                            <div v-for="(week, column) in columns" :key="column" class="flex flex-col gap-[3px]">
                                <span v-for="(day, row) in week" :key="row" class="h-[11px] w-[11px] rounded-[2px]"
                                    :class="day ? levelClasses[day.level] : 'bg-transparent'" :title="day ? describe(day) : undefined"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-2 flex items-center justify-between gap-2 text-[10px] text-muted-foreground">
                    <span v-if="account.private" :title="'Private contributions are counted but not broken down'">incl. {{ account.private }} private</span>
                    <span v-else></span>
                    <span class="flex items-center gap-[3px]">
                        Less
                        <span v-for="(levelClass, level) in levelClasses" :key="level" class="h-[11px] w-[11px] rounded-[2px]" :class="levelClass"></span>
                        More
                    </span>
                </div>

                <div class="mt-3 grid grid-cols-3 gap-1.5 widget-md:grid-cols-6">
                    <div v-for="item in breakdown" :key="item.label" class="rounded-lg border border-border/50 bg-background/40 px-2 py-1.5">
                        <div class="flex items-center gap-1 text-[10px] text-muted-foreground">
                            <Icon :icon="item.icon" class="h-3 w-3 shrink-0" />
                            <span class="truncate">{{ item.label }}</span>
                        </div>
                        <div class="font-mono text-sm font-bold text-foreground">{{ formatCount(item.value) }}{{ item.suffix ?? '' }}</div>
                    </div>
                </div>
            </template>
        </div>
    </WidgetShell>
</template>
