import { defineStore } from 'pinia';

/**
 * Dashboard edit mode, shared by the header (Edit button, add page) and the dashboard page.
 */
export const useDashboardEditStore = defineStore('dashboardEdit', {
    state: () => ({
        editing: false,
    }),
    actions: {
        start() {
            this.editing = true;
        },
        stop() {
            this.editing = false;
        },
    },
});
