import { defineStore } from 'pinia';

export interface AgendaEvent {
    id: string | number;
    type: string; // 'movie', 'series', 'todo', 'appointment', etc.
    title: string;
    date: string; // YYYY-MM-DD
    poster?: string | null;
    hasFile?: boolean;
    [key: string]: any; // Allow generic extra properties
}

export const useAgendaStore = defineStore('agenda', {
    state: () => ({
        events: [] as AgendaEvent[],
    }),
    actions: {
        setEvents(events: AgendaEvent[]) {
            this.events = events;
        },
        addEvent(event: AgendaEvent) {
            this.events.push(event);
        },
        clearEvents() {
            this.events = [];
        }
    },
    getters: {
        getEventsByDate: (state) => (dateStr: string) => {
            return state.events.filter(e => e.date === dateStr);
        },
        getGroupedEvents: (state) => {
            const groups: Record<string, AgendaEvent[]> = {};
            state.events.forEach(item => {
                if (!item.date) {
return;
}

                if (!groups[item.date]) {
groups[item.date] = [];
}

                groups[item.date].push(item);
            });

            // Sort keys chronologically
            return Object.keys(groups).sort().map(date => ({
                date,
                items: groups[date]
            }));
        }
    }
});
