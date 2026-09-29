<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { router, usePage } from '@inertiajs/vue3';
import {
    useDocumentVisibility,
    useEventListener,
    useLocalStorage,
    useMediaQuery,
    usePreferredReducedMotion,
    useWindowScroll,
} from '@vueuse/core';
import {
    computed,
    onMounted,
    onUnmounted,
    provide,
    ref,
    shallowRef,
    useTemplateRef,
    watch,
} from 'vue';
import {
    destroy,
    update,
} from '@/actions/App/Http/Controllers/DashboardPageController';
import { useSnapPanels } from '@/composables/useSnapPanels';
import { useAgendaStore } from '@/stores/useAgendaStore';
import { useDashboardEditStore } from '@/stores/useDashboardEditStore';
import { dashboardContextKey } from './dashboardContext';
import EditBar from './editor/EditBar.vue';
import { layoutEditorKey, layoutOperations } from './editor/layoutEditor';
import LayoutList from './editor/LayoutList.vue';
import WidgetCatalog from './editor/WidgetCatalog.vue';
import type { DashboardLayout, LayoutItem, ZoneName } from './layout';
import {
    DASHBOARD_GRID_CLASS,
    defaultLayout,
    MIDDLE_ZONE_CONTAINER_CLASS,
    placedWidgets,
    pruneLayout,
} from './layout';
import MobileDashboardBar from './MobileDashboardBar.vue';
import { widgetsFor } from './widgets/registry';

type DashboardPage = {
    id: number;
    name: string;
    slug: string;
    is_default: boolean;
    layout: DashboardLayout | null;
};

const props = defineProps<{
    dashboardPage: DashboardPage;
    agenda_cached?: any[];
    agenda?: any[];
    netdata?: any[];
}>();

const page = usePage();
const agendaStore = useAgendaStore();
const editStore = useDashboardEditStore();

/* ---------------------------------------------------------------- Netdata (live) */

const netdataServers = ref<any[]>(props.netdata || []);
const loadingNetdata = ref(!props.netdata);
const netdataTimeframes = useLocalStorage<Record<number, string>>(
    'netdataTimeframes',
    {},
);
let pollingInterval: ReturnType<typeof setInterval> | null = null;

/**
 * Never more than one Netdata request at a time: a slow or unreachable server would
 * otherwise stack requests every 3 seconds and tie up the web server's few PHP workers.
 * While it keeps failing, the poll backs off (3s, 6s, 12s … up to 30s).
 */
let netdataInFlight = false;
let netdataFailures = 0;
let lastNetdataFetch = 0;

const fetchNetdata = async (force = false): Promise<void> => {
    const backoff = Math.min(30_000, 3000 * 2 ** netdataFailures);

    if (
        netdataInFlight ||
        (!force && Date.now() - lastNetdataFetch < backoff)
    ) {
        return;
    }

    netdataInFlight = true;
    lastNetdataFetch = Date.now();

    try {
        const queryParams = new URLSearchParams();

        for (const [id, timeframe] of Object.entries(netdataTimeframes.value)) {
            queryParams.append(`timeframes[${id}]`, timeframe);
        }

        const qs = queryParams.toString() ? `?${queryParams.toString()}` : '';
        const response = await fetch(`/api/netdata${qs}`, {
            signal: AbortSignal.timeout(20_000),
        });

        if (response.ok) {
            const servers = (await response.json()).servers || [];
            netdataServers.value = servers;
            netdataFailures = servers.some(
                (server: any) => server.stats === null,
            )
                ? netdataFailures + 1
                : 0;
        } else {
            netdataFailures++;
        }
    } catch {
        netdataFailures++;
    } finally {
        netdataInFlight = false;
        loadingNetdata.value = false;
    }
};

provide(dashboardContextKey, {
    refreshNetdata: () => void fetchNetdata(true),
    setNetdataTimeframe: (serverId: number, timeframe: string) => {
        netdataTimeframes.value[serverId] = timeframe;
        void fetchNetdata(true);
    },
});

/* ---------------------------------------------------------------- Widgets and layout */

/** What widgets build their props from: page props, with live Netdata data. */
const widgetData = computed<Record<string, any>>(() => ({
    ...page.props,
    netdata:
        loadingNetdata.value && !netdataServers.value.length
            ? props.netdata
            : netdataServers.value,
    netdataTimeframes: netdataTimeframes.value,
}));

const available = computed(() => widgetsFor(widgetData.value));

/** The default page's built-in layout, generated once per page so item ids stay stable. */
const builtIn = shallowRef<DashboardLayout>(defaultLayout(available.value));
watch(
    () => props.dashboardPage.id,
    () => {
        builtIn.value = defaultLayout(available.value);
    },
);

const savedLayout = computed<DashboardLayout>(() =>
    pruneLayout(props.dashboardPage.layout ?? builtIn.value, available.value),
);

/* ---------------------------------------------------------------- Edit mode */

const clone = (layout: DashboardLayout): DashboardLayout =>
    JSON.parse(JSON.stringify(layout));

const draft = ref<DashboardLayout>(clone(savedLayout.value));
const draftName = ref(props.dashboardPage.name);
const catalogOpen = ref(false);
const saving = ref(false);
const saveError = ref<string | null>(null);

const startEditing = (): void => {
    draft.value = clone(savedLayout.value);
    draftName.value = props.dashboardPage.name;
    saveError.value = null;
    catalogOpen.value = true;
};

watch(
    () => editStore.editing,
    (editing) => {
        if (editing) {
            startEditing();
        } else {
            catalogOpen.value = false;
            collapsedIds.value = new Set();
        }
    },
);

// Adding a page lands on it: keep editing there
watch(
    () => props.dashboardPage.id,
    () => {
        if (editStore.editing) {
            startEditing();
        }
    },
);

const layout = computed(() =>
    editStore.editing ? draft.value : savedLayout.value,
);

const dirty = computed(
    () =>
        editStore.editing &&
        (JSON.stringify(draft.value) !== JSON.stringify(savedLayout.value) ||
            draftName.value !== props.dashboardPage.name),
);

/** Widgets not on this page yet, for the catalog. */
const unplaced = computed(() => {
    const placed = placedWidgets(draft.value);

    return available.value.filter((definition) => !placed.has(definition.id));
});

/** Collapsed widgets (edit mode only, not saved). */
const collapsedIds = ref(new Set<string>());

const toggleCollapsed = (id: string): void => {
    const next = new Set(collapsedIds.value);

    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }

    collapsedIds.value = next;
};

const widgetIds = (): string[] => {
    const ids: string[] = [];
    const visit = (items: LayoutItem[]): void =>
        items.forEach((item) => {
            if (item.kind === 'widget') {
                ids.push(item.id);
            } else if (item.kind === 'section') {
                visit(item.items);
            } else {
                item.tabs.forEach((tab) => visit(tab.items));
            }
        });

    Object.values(draft.value.zones).forEach(visit);

    return ids;
};

const allCollapsed = computed(
    () =>
        collapsedIds.value.size > 0 &&
        widgetIds().every((id) => collapsedIds.value.has(id)),
);
const toggleAllCollapsed = (): void => {
    collapsedIds.value = allCollapsed.value ? new Set() : new Set(widgetIds());
};

provide(layoutEditorKey, {
    editing: computed(() => editStore.editing),
    available,
    data: widgetData,
    ...layoutOperations(draft),
    isCollapsed: (id: string) => collapsedIds.value.has(id),
    toggleCollapsed,
});

const addToMiddle = (item: LayoutItem): void => {
    draft.value.zones.middle.push(item);
};

/** The team is part of every dashboard route; Wayfinder can't fill it in on its own here. */
const currentTeam = (): string => page.props.currentTeam?.slug ?? '';

const pageUrl = () =>
    update.url({
        current_team: currentTeam(),
        dashboardPage: props.dashboardPage.id,
    });

/**
 * Saving only changes the page (and the page list in the header), so only reload those and
 * keep the dashboard as it is. A full reload would remount every widget and refetch all their
 * deferred data, which made saving look like it never came back.
 */
const keepDashboard = {
    preserveScroll: true,
    preserveState: true,
    only: ['dashboardPage', 'dashboardPages'],
};

const save = (): void => {
    saving.value = true;
    saveError.value = null;
    router.patch(
        pageUrl(),
        { name: draftName.value.trim(), layout: draft.value },
        {
            ...keepDashboard,
            onSuccess: () => editStore.stop(),
            onError: (errors) => {
                saveError.value = Object.values(errors).join(' ');
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
};

const reset = (): void => {
    const message = props.dashboardPage.is_default
        ? 'Reset this page to the built-in layout? Your arrangement will be lost.'
        : 'Remove every widget from this page?';

    if (window.confirm(message)) {
        router.patch(
            pageUrl(),
            { layout: null },
            { ...keepDashboard, onSuccess: () => editStore.stop() },
        );
    }
};

const deletePage = (): void => {
    if (window.confirm(`Delete the "${props.dashboardPage.name}" page?`)) {
        router.delete(
            destroy.url({
                current_team: currentTeam(),
                dashboardPage: props.dashboardPage.id,
            }),
            { onSuccess: () => editStore.stop() },
        );
    }
};

const cancel = (): void => {
    if (!dirty.value || window.confirm('Discard your changes?')) {
        editStore.stop();
    }
};

// Don't lose unsaved changes by navigating away
const removeNavigationGuard = router.on('before', (event) => {
    const visit = event.detail.visit;
    // Widgets refresh themselves with partial reloads of this same page; only real navigation counts
    const leavesPage =
        visit.method === 'get' &&
        !visit.only.length &&
        visit.url.pathname !== window.location.pathname;

    if (
        dirty.value &&
        leavesPage &&
        !window.confirm('Leave without saving your dashboard changes?')
    ) {
        return false;
    }
});

useEventListener('beforeunload', (event: BeforeUnloadEvent) => {
    if (dirty.value) {
        event.preventDefault();
    }
});

useEventListener('keydown', (event: KeyboardEvent) => {
    if (event.key === 'Escape' && editStore.editing && !catalogOpen.value) {
        cancel();
    }
});

/* ---------------------------------------------------------------- Lifecycle */

const visibility = useDocumentVisibility();

onMounted(() => {
    if (props.agenda_cached) {
        agendaStore.setEvents(props.agenda_cached);
    } else if (props.agenda) {
        agendaStore.setEvents(props.agenda);
    }

    watch(
        () => props.agenda,
        (agenda) => {
            if (agenda) {
                agendaStore.setEvents(agenda);
            }
        },
    );

    if (
        !props.netdata ||
        props.netdata.some((server) => !server.stats || server.stats.is_partial)
    ) {
        void fetchNetdata(true);
    }

    pollingInterval = setInterval(() => {
        if (visibility.value === 'visible') {
            void fetchNetdata();
        }
    }, 3000);
});

onUnmounted(() => {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }

    removeNavigationGuard();
    editStore.stop();
});

/* ---------------------------------------------------------------- Zones: desktop grid, phone panels */

const zones: {
    name: ZoneName;
    label: string;
    editLabel: string;
    desktopClass: string;
}[] = [
    {
        name: 'left',
        label: 'Left',
        editLabel: 'Left · 1 column',
        desktopClass: 'col-left min-w-0',
    },
    {
        name: 'middle',
        label: 'Main',
        editLabel: 'Middle · 3 columns',
        desktopClass: 'col-mid min-w-0',
    },
    {
        name: 'right',
        label: 'Right',
        editLabel: 'Right · 1 column',
        desktopClass: 'col-right min-w-0',
    },
];

const isDesktop = useMediaQuery('(min-width: 768px)');
const reducedMotion = usePreferredReducedMotion();
const scrollBehavior = (): ScrollBehavior =>
    reducedMotion.value === 'reduce' ? 'auto' : 'smooth';

const track = useTemplateRef<HTMLElement>('track');
const mobileTrack = computed(() => (isDesktop.value ? null : track.value));
// Dragging switches panels, except in edit mode where dragging moves widgets
const {
    progress: panelProgress,
    active: activePanel,
    scrollTo: scrollToPanel,
} = useSnapPanels(
    mobileTrack,
    zones.length,
    1,
    computed(() => !editStore.editing),
);

/** Each panel scrolls on its own: screen height minus the page padding and the bottom bar (56px). The header moves into the bar on a phone. */
const mobileTrackClass =
    'relative -mx-4 flex snap-x snap-mandatory overflow-x-auto overscroll-x-contain [scrollbar-width:none] [&::-webkit-scrollbar]:hidden';
const mobileTrackStyle = {
    height: 'calc(100dvh - 2rem - 56px - env(safe-area-inset-bottom))',
};

const activePanelElement = (): HTMLElement | undefined =>
    track.value?.children[activePanel.value] as HTMLElement | undefined;

/** Whether the visible panel is scrolled down more than a screen (shows ↑). */
const panelScrolled = ref(false);
useEventListener(
    track,
    'scroll',
    () => {
        const panel = activePanelElement();
        panelScrolled.value = !!panel && panel.scrollTop > panel.clientHeight;
    },
    { capture: true, passive: true },
);

const panelToTop = (): void =>
    activePanelElement()?.scrollTo({ top: 0, behavior: scrollBehavior() });

const { y: windowY } = useWindowScroll();
// Browser only: there's no window when the page is rendered on the server
const windowScrolled = computed(
    () => typeof window !== 'undefined' && windowY.value > window.innerHeight,
);
const windowToTop = (): void =>
    window.scrollTo({ top: 0, behavior: scrollBehavior() });
const isEmpty = computed(
    () =>
        !layout.value.zones.left.length &&
        !layout.value.zones.middle.length &&
        !layout.value.zones.right.length,
);
</script>

<template>
    <!-- In edit mode the widget catalog sits beside the dashboard (desktop) or above it (phone), as part of the page -->
    <div
        class="flex h-full flex-1 gap-4 p-4 text-foreground"
        :class="[
            isDesktop ? 'flex-row items-start' : 'flex-col',
            { 'pb-28': editStore.editing, 'overflow-hidden': !isDesktop },
        ]"
    >
        <div
            class="flex min-w-0 flex-1 flex-col gap-4 rounded-xl"
            :class="{ 'order-2': !isDesktop }"
        >
            <div
                v-if="isEmpty && !editStore.editing"
                class="flex flex-col items-center justify-center gap-2 py-24 text-center text-muted-foreground"
            >
                <Icon
                    icon="lucide:layout-dashboard"
                    class="h-8 w-8 opacity-40"
                />
                <p class="text-sm">This page is empty.</p>
                <p v-if="page.props.canEditDashboard" class="text-xs">
                    Press the pencil in the header to add widgets.
                </p>
            </div>

            <!-- Desktop: three zones side by side. Phone: the same zones as snapping panels (Left · Main · Right). -->
            <div
                v-else
                ref="track"
                :class="
                    isDesktop
                        ? ['layout', DASHBOARD_GRID_CLASS]
                        : mobileTrackClass
                "
                :style="isDesktop ? undefined : mobileTrackStyle"
            >
                <component
                    :is="
                        isDesktop && zone.name !== 'middle'
                            ? 'aside'
                            : 'section'
                    "
                    v-for="zone in zones"
                    :key="zone.name"
                    :class="[
                        isDesktop
                            ? zone.desktopClass
                            : 'w-full shrink-0 snap-center snap-always overflow-y-auto overscroll-y-contain px-4 pb-4',
                        zone.name === 'middle'
                            ? MIDDLE_ZONE_CONTAINER_CLASS
                            : '',
                    ]"
                    :aria-label="zone.label"
                >
                    <p
                        v-if="editStore.editing"
                        class="mb-1.5 text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        {{ zone.editLabel }}
                    </p>
                    <LayoutList
                        :items="layout.zones[zone.name]"
                        kind="zone"
                        :owner="layout.zones"
                        :owner-key="zone.name"
                        :columns="zone.name === 'middle' ? 3 : null"
                        :is-zone="zone.name === 'middle'"
                    />
                </component>
            </div>

            <MobileDashboardBar
                v-if="!isDesktop && !editStore.editing && !isEmpty"
                :panels="zones.map((zone) => zone.label)"
                :progress="panelProgress"
                :active="activePanel"
                :show-back-to-top="panelScrolled"
                @select="scrollToPanel"
                @top="panelToTop"
            />

            <!-- Desktop: back to top once you've scrolled down a screen -->
            <button
                v-if="isDesktop && windowScrolled"
                type="button"
                class="fixed right-6 bottom-6 z-30 flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border bg-card/90 shadow-lg backdrop-blur-xl hover:border-primary/50"
                aria-label="Back to top"
                @click="windowToTop"
            >
                <Icon icon="lucide:arrow-up" class="h-4 w-4" />
            </button>

            <template v-if="editStore.editing">
                <EditBar
                    :sheet-open="catalogOpen"
                    :all-collapsed="allCollapsed"
                    @toggle-collapse="toggleAllCollapsed"
                    :page-name="draftName"
                    :is-default-page="dashboardPage.is_default"
                    :dirty="dirty"
                    :saving="saving"
                    :error="saveError"
                    @rename="draftName = $event"
                    @widgets="catalogOpen = !catalogOpen"
                    @reset="reset"
                    @delete="deletePage"
                    @cancel="cancel"
                    @save="save"
                />
            </template>
        </div>

        <aside
            v-if="editStore.editing && catalogOpen"
            class="shrink-0"
            :class="
                isDesktop
                    ? 'sticky top-4 max-h-[calc(100dvh-2rem)] w-80 overflow-y-auto'
                    : 'order-1 max-h-[45dvh] overflow-y-auto'
            "
        >
            <WidgetCatalog
                :widgets="unplaced"
                @add="addToMiddle"
                @close="catalogOpen = false"
            />
        </aside>
    </div>
</template>
