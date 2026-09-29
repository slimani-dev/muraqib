<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { today, getLocalTimeZone } from '@internationalized/date';
import {
    format,
    isToday,
    isTomorrow,
    isYesterday,
    formatDistanceToNowStrict,
    startOfDay,
} from 'date-fns';
import { ref, computed, watch } from 'vue';
import { Calendar } from '@/components/ui/calendar';
import type { AgendaEvent } from '@/stores/useAgendaStore';
import { useAgendaStore } from '@/stores/useAgendaStore';
import AgendaItemCard from './AgendaItemCard.vue';
import type { WidgetMode } from './widgets/widgetMode';
import WidgetShell from './widgets/WidgetShell.vue';

const props = withDefaults(
    defineProps<{
        /**
         * Events to show (media releases for now; tasks and Google Calendar later).
         * When omitted the widget reads the shared agenda store.
         */
        events?: AgendaEvent[] | null;
        mode?: WidgetMode;
    }>(),
    {
        events: null,
        mode: 'desktop',
    },
);

const agendaStore = useAgendaStore();

watch(
    () => props.events,
    (events) => {
        if (events) {
            agendaStore.setEvents(events);
        }
    },
    { immediate: true },
);

const value = ref<any>(today(getLocalTimeZone()));
const viewMode = ref<'calendar' | 'list'>('calendar');

// Format the selected date to match the agenda item dates (YYYY-MM-DD)
const selectedDateString = computed(() => {
    if (!value.value) {
        return null;
    }

    const year = value.value.year;
    const month = String(value.value.month).padStart(2, '0');
    const day = String(value.value.day).padStart(2, '0');

    return `${year}-${month}-${day}`;
});

const selectedAgendaItems = computed(() => {
    if (!selectedDateString.value) {
        return [];
    }

    return agendaStore.getEventsByDate(selectedDateString.value);
});

// For calendar dots
const datesWithReleases = computed(() => {
    const dates = new Set<string>();
    agendaStore.events.forEach((item) => {
        if (item.date) {
            dates.add(item.date);
        }
    });

    return dates;
});

const groupedAgenda = computed(() => agendaStore.getGroupedEvents);

// Helper for relative time text
const getRelativeDateText = (dateString: string) => {
    const d = startOfDay(new Date(dateString));

    if (isToday(d)) {
        return 'Today';
    }

    if (isTomorrow(d)) {
        return 'Tomorrow';
    }

    if (isYesterday(d)) {
        return 'Yesterday';
    }

    return `in ${formatDistanceToNowStrict(d)}`;
};
</script>

<template>
    <WidgetShell v-slot="{ layout }" :mode="mode">
        <div
            class="col-span-full flex flex-col overflow-hidden rounded-xl border bg-card text-card-foreground shadow-sm"
        >
            <!-- Header -->
            <div
                class="flex items-center justify-between border-b bg-muted/10 p-4"
            >
                <h3
                    class="flex items-center gap-2 text-[13px] font-bold tracking-wider text-foreground uppercase"
                >
                    <Icon icon="lucide:calendar" class="h-4 w-4 text-primary" />
                    Calendar
                </h3>
                <!-- 1 column only: 2 and 3 columns show the calendar and the day's list together -->
                <div
                    v-if="layout === 'mobile'"
                    class="flex items-center rounded-lg bg-muted p-1"
                >
                    <button
                        @click="viewMode = 'calendar'"
                        class="rounded-md px-3 py-1 text-xs font-bold transition-colors"
                        :class="
                            viewMode === 'calendar'
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                    >
                        Calendar
                    </button>
                    <button
                        @click="viewMode = 'list'"
                        class="rounded-md px-3 py-1 text-xs font-bold transition-colors"
                        :class="
                            viewMode === 'list'
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                    >
                        List
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div
                v-if="layout !== 'mobile' || viewMode === 'calendar'"
                class="flex flex-col widget-md:grid widget-md:grid-cols-2 widget-lg:grid-cols-[minmax(17rem,20rem)_1fr]"
            >
                <!-- Calendar Sidebar -->
                <div
                    class="flex w-full justify-center border-b bg-card p-4 widget-md:items-start widget-md:border-r widget-md:border-b-0"
                >
                    <Calendar
                        v-model="value"
                        class="w-full border-none shadow-none"
                    >
                        <template #cell="{ date }">
                            <div
                                class="relative flex h-full w-full flex-col items-center justify-center"
                            >
                                <span>{{ date.day }}</span>
                                <div
                                    v-if="
                                        datesWithReleases.has(
                                            `${date.year}-${String(date.month).padStart(2, '0')}-${String(date.day).padStart(2, '0')}`,
                                        )
                                    "
                                    class="absolute bottom-1 h-1 w-1 rounded-full bg-primary"
                                ></div>
                            </div>
                        </template>
                    </Calendar>
                </div>

                <!-- Details Panel -->
                <div class="flex flex-col p-4">
                    <div
                        class="mb-4 flex items-center gap-2 rounded-lg bg-muted/30 px-3 py-2"
                    >
                        <h4 class="text-sm font-bold text-foreground">
                            {{
                                selectedDateString
                                    ? format(
                                          new Date(selectedDateString),
                                          'MMMM do, yyyy',
                                      )
                                    : 'Select a Date'
                            }}
                        </h4>
                        <span
                            v-if="selectedDateString"
                            class="rounded-full border bg-background px-2 text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                        >
                            {{ getRelativeDateText(selectedDateString) }}
                        </span>
                    </div>

                    <div
                        class="flex-1 space-y-3 overflow-y-auto pr-2 widget-md:grid widget-md:max-h-[22rem] widget-md:grid-cols-[repeat(auto-fill,minmax(15rem,1fr))] widget-md:content-start widget-md:gap-3 widget-md:space-y-0"
                        v-if="selectedAgendaItems.length"
                    >
                        <AgendaItemCard
                            v-for="item in selectedAgendaItems"
                            :key="item.id"
                            :item="item"
                        />
                    </div>

                    <div
                        v-else
                        class="flex flex-1 flex-col items-center justify-center text-muted-foreground"
                    >
                        <Icon
                            icon="lucide:calendar-x"
                            class="mb-2 h-10 w-10 opacity-30"
                        />
                        <p class="text-[13px] font-medium">
                            Nothing scheduled.
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="flex max-h-[600px] flex-col space-y-4 overflow-y-auto p-4"
            >
                <div
                    v-if="groupedAgenda.length === 0"
                    class="flex flex-1 flex-col items-center justify-center py-10 text-muted-foreground"
                >
                    <Icon
                        icon="lucide:inbox"
                        class="mb-2 h-10 w-10 opacity-30"
                    />
                    <p class="text-[13px] font-medium">Nothing coming up.</p>
                </div>

                <div
                    v-for="group in groupedAgenda"
                    :key="group.date"
                    class="space-y-2"
                >
                    <div
                        class="sticky top-0 z-10 flex items-center gap-2 border-b bg-card pt-1 pb-1"
                    >
                        <h4
                            class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                        >
                            {{
                                format(
                                    new Date(group.date),
                                    'EEEE, MMMM do, yyyy',
                                )
                            }}
                        </h4>
                        <span
                            class="text-[9px] font-bold tracking-wider text-muted-foreground/70 uppercase"
                        >
                            ({{ getRelativeDateText(group.date) }})
                        </span>
                    </div>
                    <div
                        class="flex flex-col gap-2 widget-md:grid widget-md:grid-cols-[repeat(auto-fill,minmax(15rem,1fr))]"
                    >
                        <AgendaItemCard
                            v-for="item in group.items"
                            :key="item.id"
                            :item="item"
                            compact
                        />
                    </div>
                </div>
            </div>
        </div>
    </WidgetShell>
</template>
