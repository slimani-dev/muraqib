<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { router, usePoll } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import { Skeleton } from '../ui/skeleton';
import { age } from './repos/repositories';
import SectionLabel from './SectionLabel.vue';
import type { WidgetMode } from './widgets/widgetMode';
import WidgetShell from './widgets/WidgetShell.vue';

type Notification = {
    id: string;
    unread: boolean;
    reason: string;
    updated_at: string;
    title: string;
    type: string;
    number: string | null;
    repository: string;
    repository_avatar: string | null;
    url: string;
    saved: boolean;
};

/** One entry of the dashboard's `github_inbox` prop (App\Services\Git\GitHubInbox::accounts). */
export type GitHubInboxAccount = {
    id: number;
    name: string;
    username: string | null;
    error: boolean;
    notifications: Notification[];
    saved: Notification[];
};

type Action = 'read' | 'done' | 'unsubscribe' | 'save' | 'unsave';

const props = withDefaults(defineProps<{
    /** Undefined while the deferred prop loads. */
    accounts?: GitHubInboxAccount[];
    mode?: WidgetMode;
}>(), {
    accounts: undefined,
    mode: 'desktop',
});

/** GitHub asks clients not to poll notifications more than once a minute. */
usePoll(60_000, { only: ['github_inbox'] });

const selectedAccountId = ref<number | null>(null);
const account = computed(() => props.accounts?.find((candidate) => candidate.id === selectedAccountId.value) ?? props.accounts?.[0] ?? null);

const filter = ref<'all' | 'unread' | 'saved'>('all');
const search = ref('');
const selected = ref<Set<string>>(new Set());

/** Optimistic updates until the next reload confirms them. */
const hidden = reactive(new Set<string>());
const readLocally = reactive(new Set<string>());
const savedLocally = reactive(new Map<string, boolean>());

watch(() => props.accounts, () => {
    hidden.clear();
    readLocally.clear();
    savedLocally.clear();
});

const withLocalState = (notification: Notification): Notification => ({
    ...notification,
    unread: notification.unread && !readLocally.has(notification.id),
    saved: savedLocally.get(notification.id) ?? notification.saved,
});

const inbox = computed(() => (account.value?.notifications ?? []).filter((notification) => !hidden.has(notification.id)).map(withLocalState));
const unreadCount = computed(() => inbox.value.filter((notification) => notification.unread).length);

const visible = computed(() => {
    const list = filter.value === 'saved'
        ? (account.value?.saved ?? []).map(withLocalState).filter((notification) => notification.saved)
        : inbox.value.filter((notification) => filter.value === 'all' || notification.unread);
    const query = search.value.trim().toLowerCase();

    return query ? list.filter((notification) => `${notification.repository} ${notification.title}`.toLowerCase().includes(query)) : list;
});

const busy = ref(false);

const act = (action: Action, threads: string[]): void => {
    if (!account.value || !threads.length) {
        return;
    }

    threads.forEach((id) => {
        if (action === 'done') {
            hidden.add(id);
        } else if (action === 'read') {
            readLocally.add(id);
        } else if (action === 'save' || action === 'unsave') {
            savedLocally.set(id, action === 'save');
        }
    });

    selected.value = new Set();
    busy.value = true;
    router.post(`/api/github/${account.value.id}/notifications`, { action, threads }, {
        preserveScroll: true,
        preserveState: true,
        only: ['github_inbox'],
        onFinish: () => {
            busy.value = false;
        },
    });
};

/** Open on GitHub, and mark as read like GitHub does. */
const open = (notification: Notification): void => {
    window.open(notification.url, '_blank', 'noopener');

    if (notification.unread) {
        act('read', [notification.id]);
    }
};

const toggle = (id: string): void => {
    const next = new Set(selected.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    selected.value = next;
};

const allSelected = computed(() => visible.value.length > 0 && visible.value.every((notification) => selected.value.has(notification.id)));
const toggleAll = (): void => {
    selected.value = allSelected.value ? new Set() : new Set(visible.value.map((notification) => notification.id));
};

const refreshing = ref(false);
const refresh = (): void => {
    refreshing.value = true;
    router.reload({ only: ['github_inbox'], onFinish: () => (refreshing.value = false) });
};

const typeIcons: Record<string, { icon: string; color: string }> = {
    Issue: { icon: 'lucide:circle-dot', color: 'text-chart-3' },
    PullRequest: { icon: 'lucide:git-pull-request', color: 'text-violet-400' },
    Discussion: { icon: 'lucide:messages-square', color: 'text-chart-1' },
    Release: { icon: 'lucide:tag', color: 'text-chart-4' },
    Commit: { icon: 'lucide:git-commit-horizontal', color: 'text-muted-foreground' },
    CheckSuite: { icon: 'lucide:circle-play', color: 'text-destructive' },
    WorkflowRun: { icon: 'lucide:circle-play', color: 'text-destructive' },
};

const reasons: Record<string, string> = {
    assign: 'assigned',
    review_requested: 'review requested',
    team_mention: 'team mention',
    ci_activity: 'ci activity',
    security_alert: 'security',
    state_change: 'state change',
    manual: 'subscribed',
};
</script>

<template>
    <WidgetShell :mode="mode">
        <div class="flex h-full flex-col rounded-xl border bg-card/70 p-3.5 text-card-foreground shadow-xl backdrop-blur-xl">
            <div class="flex items-center justify-between gap-2">
                <SectionLabel icon="bell" text="GitHub Inbox" />
                <div class="-mt-2 flex items-center gap-1">
                    <button v-for="candidate in accounts && accounts.length > 1 ? accounts : []" :key="candidate.id" type="button"
                        class="cursor-pointer rounded-md px-2 py-0.5 text-[10px] font-semibold"
                        :class="candidate.id === account?.id ? 'bg-muted text-foreground' : 'text-muted-foreground hover:text-foreground'"
                        @click="selectedAccountId = candidate.id">
                        {{ candidate.username ?? candidate.name }}
                    </button>
                    <button type="button" class="flex h-5 w-5 cursor-pointer items-center justify-center rounded-full text-muted-foreground hover:bg-muted hover:text-foreground"
                        title="Refresh" @click="refresh">
                        <Icon icon="lucide:refresh-cw" class="h-3 w-3" :class="{ 'animate-spin': refreshing }" />
                    </button>
                </div>
            </div>

            <div v-if="accounts === undefined" class="space-y-2">
                <Skeleton v-for="i in 4" :key="i" class="h-9 w-full" />
            </div>

            <div v-else-if="!account" class="flex flex-col items-center gap-1.5 py-4 text-center">
                <Icon icon="simple-icons:github" class="h-5 w-5 text-muted-foreground/50" />
                <p class="text-xs text-muted-foreground">Add a GitHub account with a classic token (notifications or repo scope) to see your inbox.</p>
                <a href="/admin/git-accounts" class="text-xs font-medium text-primary hover:underline">Manage Git accounts</a>
            </div>

            <template v-else>
                <!-- Filters -->
                <div class="mb-2 flex flex-wrap items-center gap-2">
                    <div class="flex rounded-lg bg-muted p-0.5 text-[11px] font-semibold">
                        <button v-for="option in (['all', 'unread', 'saved'] as const)" :key="option" type="button"
                            class="cursor-pointer rounded-md px-2.5 py-1 capitalize transition-colors"
                            :class="filter === option ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                            @click="filter = option; selected = new Set()">
                            {{ option }}<span v-if="option === 'unread' && unreadCount" class="ml-1 text-primary">{{ unreadCount }}</span>
                        </button>
                    </div>
                    <input v-model="search" type="search" placeholder="Filter notifications"
                        class="hidden h-7 min-w-0 flex-1 rounded-lg border border-border/50 bg-background/40 px-2.5 text-[12px] outline-none placeholder:text-muted-foreground focus:border-primary/50 widget-md:block" />
                </div>

                <p v-if="account.error" class="mb-2 rounded-lg border border-destructive/30 bg-destructive/10 px-2.5 py-1.5 text-[11px] text-destructive">
                    Couldn't load notifications. Check that the token is a classic token with the notifications or repo scope.
                </p>

                <!-- Bulk actions -->
                <div v-if="visible.length" class="mb-1 flex h-7 items-center gap-2 px-1 text-[11px]">
                    <input type="checkbox" class="h-3.5 w-3.5 cursor-pointer accent-primary" :checked="allSelected" title="Select all" @change="toggleAll" />
                    <template v-if="selected.size">
                        <span class="font-semibold text-foreground">{{ selected.size }} selected</span>
                        <button type="button" class="flex cursor-pointer items-center gap-1 rounded-md border border-border/60 px-2 py-0.5 hover:bg-muted" @click="act('done', [...selected])">
                            <Icon icon="lucide:check" class="h-3 w-3" /> Done
                        </button>
                        <button type="button" class="flex cursor-pointer items-center gap-1 rounded-md border border-border/60 px-2 py-0.5 hover:bg-muted" @click="act('read', [...selected])">
                            <Icon icon="lucide:mail-open" class="h-3 w-3" /> Read
                        </button>
                        <button type="button" class="flex cursor-pointer items-center gap-1 rounded-md border border-border/60 px-2 py-0.5 hover:bg-muted" @click="act('unsubscribe', [...selected])">
                            <Icon icon="lucide:bell-off" class="h-3 w-3" /> Unsubscribe
                        </button>
                    </template>
                    <span v-else class="text-muted-foreground">{{ visible.length }} notification{{ visible.length === 1 ? '' : 's' }}</span>
                </div>

                <!-- List -->
                <div v-if="visible.length" class="divide-y divide-border/40 overflow-hidden rounded-lg border border-border/50">
                    <div v-for="notification in visible" :key="notification.id"
                        class="group relative flex cursor-pointer items-center gap-2 px-2 py-2 transition-colors hover:bg-muted/40"
                        :class="{ 'bg-primary/5': selected.has(notification.id) }" @click="open(notification)">
                        <span class="absolute top-1/2 left-0.5 h-1.5 w-1.5 -translate-y-1/2 rounded-full" :class="notification.unread ? 'bg-primary' : 'bg-transparent'"></span>
                        <input type="checkbox" class="ml-1 h-3.5 w-3.5 shrink-0 cursor-pointer accent-primary" :checked="selected.has(notification.id)"
                            @click.stop @change="toggle(notification.id)" />
                        <Icon :icon="(typeIcons[notification.type] ?? { icon: 'lucide:bell' }).icon" class="h-3.5 w-3.5 shrink-0"
                            :class="(typeIcons[notification.type] ?? { color: 'text-muted-foreground' }).color" :title="notification.type" />
                        <div class="flex min-w-0 flex-1 flex-col">
                            <span class="truncate text-[10px] text-muted-foreground">{{ notification.repository }}<template v-if="notification.number"> #{{ notification.number }}</template></span>
                            <span class="truncate text-[12px]" :class="notification.unread ? 'font-semibold text-foreground' : 'text-foreground/75'">{{ notification.title }}</span>
                        </div>
                        <span class="hidden shrink-0 text-[10px] text-muted-foreground widget-md:inline">{{ reasons[notification.reason] ?? notification.reason }}</span>
                        <img v-if="notification.repository_avatar" :src="notification.repository_avatar" alt="" class="hidden h-5 w-5 shrink-0 rounded-full widget-lg:block" />
                        <span class="w-8 shrink-0 text-right font-mono text-[10px] text-muted-foreground group-hover:hidden">{{ age(notification.updated_at) }}</span>

                        <!-- Row actions (shown on hover, like GitHub) -->
                        <div class="hidden shrink-0 items-center gap-0.5 group-hover:flex" @click.stop>
                            <button v-if="notification.unread" type="button" class="flex h-6 w-6 cursor-pointer items-center justify-center rounded-md hover:bg-background" title="Mark as read" @click="act('read', [notification.id])">
                                <Icon icon="lucide:mail-open" class="h-3.5 w-3.5" />
                            </button>
                            <button v-if="filter !== 'saved'" type="button" class="flex h-6 w-6 cursor-pointer items-center justify-center rounded-md hover:bg-background" title="Mark as done" @click="act('done', [notification.id])">
                                <Icon icon="lucide:check" class="h-3.5 w-3.5" />
                            </button>
                            <button type="button" class="flex h-6 w-6 cursor-pointer items-center justify-center rounded-md hover:bg-background" title="Unsubscribe" @click="act('unsubscribe', [notification.id])">
                                <Icon icon="lucide:bell-off" class="h-3.5 w-3.5" />
                            </button>
                            <button type="button" class="flex h-6 w-6 cursor-pointer items-center justify-center rounded-md hover:bg-background"
                                :title="notification.saved ? 'Unsave' : 'Save'" @click="act(notification.saved ? 'unsave' : 'save', [notification.id])">
                                <Icon :icon="notification.saved ? 'lucide:bookmark-check' : 'lucide:bookmark'" class="h-3.5 w-3.5" :class="{ 'text-primary': notification.saved }" />
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else-if="!account.error" class="flex flex-col items-center gap-1 py-5 text-center">
                    <Icon icon="lucide:party-popper" class="h-5 w-5 text-muted-foreground/60" />
                    <p class="text-xs text-muted-foreground">{{ filter === 'saved' ? 'Nothing saved.' : filter === 'unread' ? 'No unread notifications.' : 'All caught up!' }}</p>
                </div>
            </template>
        </div>
    </WidgetShell>
</template>
