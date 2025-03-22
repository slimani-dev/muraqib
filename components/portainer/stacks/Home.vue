<script setup lang="ts">
import {useEndpointStore} from "~/stores/endpoint";
import type {ContainerInfo} from "~/types";
import MuraqibContainer from "~/components/portainer/stacks/MuraqibContainer.vue";

const store = useEndpointStore();

const endpoints = Array.isArray(store.activeEndpoint)
    ? store.activeEndpoint
    : [store.activeEndpoint];

const fetchPromises = endpoints.map((endpoint) =>
    useFetch<ContainerInfo>(`/api/portainer/endpoints/${endpoint}/docker/containers/json`, {
      server: false,
      lazy: true,
    })
);

const results = await Promise.all(fetchPromises);
const containers = computed(() =>
    results.flatMap((r) => toRaw(r.data?.value) || [])
);

</script>

<template>
  <div class="grid masonry gap-4">
    <div
        v-for="container in containers"
        :key="container.id"
        class="break-inside-avoid "
        :class="{
          'col-span-2': container.name === 'authentik-server'
        }">
      <MuraqibContainer :container="container"/>
    </div>
  </div>
</template>

<style scoped>

.masonry {
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  grid-template-rows: masonry;
}

</style>
