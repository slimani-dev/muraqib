import type { InjectionKey } from 'vue';

/**
 * Things a widget can ask of the dashboard that renders it. Widgets are rendered
 * generically from the page layout, so they reach the dashboard through this
 * instead of events.
 */
export type DashboardContext = {
    /** Fetch Netdata again right away (e.g. after a lost connection). */
    refreshNetdata: () => void;
    /** Change the chart timeframe of one Netdata server. */
    setNetdataTimeframe: (serverId: number, timeframe: string) => void;
};

export const dashboardContextKey: InjectionKey<DashboardContext> = Symbol('dashboardContext');
