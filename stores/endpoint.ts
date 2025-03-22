import {defineStore} from 'pinia';

export const useEndpointStore = defineStore('endpoint', {
  state: () => ({
    activeEndpoint: "1" as string | string[], // Store active endpoint ID
  }),
  actions: {
    setActiveEndpoint(id: string | string[] | undefined) {
      this.activeEndpoint = id ?? "1";
    },
  },
  getters: {
    getActiveEndpoint: (state) => state.activeEndpoint,
  }
});
