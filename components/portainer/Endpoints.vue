<script setup lang="ts">
import Humanize from "humanize-plus";
import UpdateCheck from "~/components/portainer/UpdateCheck.vue";
import type {Endpoint} from "~/types";
import {useEndpointStore} from "~/stores/endpoint";

const store = useEndpointStore();


const idMap = {
  1: "one",
  2: "two",
  3: "three",
  4: "four",
};

const items = [
  {label: 'Portainer Endpoints', icon: 'i-mdi-docker', slot: 'endpoint'}
]

const {
  status: endpointsStatus,
  data: endpoints,
} = useFetch<Endpoint[]>('/api/portainer/endpoints/', {
  server: false,
  lazy: true,
})

const accordionItems = computed(() =>
    (endpoints.value || []).map((endpoint) => ({
      icon: `i-mdi-number-${idMap[endpoint.id as keyof typeof idMap]}-circle-outline`,
      label: endpoint.Name.toUpperCase(),
      value: endpoint.id.toString(),
      ...endpoint,
      TotalMemory: Humanize.fileSize(endpoint.TotalMemory),
    }))
)

// Watch for endpoint data and set default when available
watch(endpoints, (newEndpoints) => {
  if (Array.isArray(newEndpoints) && newEndpoints.length > 0 && !store.getActiveEndpoint) {
    store.setActiveEndpoint(newEndpoints[0].id.toString());
  }
}, { immediate: true });

const setActive = (id: string | string[] | undefined) => {
  store.setActiveEndpoint(id);
};
</script>

<template>
  <div v-if="endpointsStatus === 'pending' || endpointsStatus === 'idle'" class="grid grid-cols-1 gap-2">
    <div class="flex flex-row px-4 py-3.5 gap-1.5">
      <USkeleton class="h-5 w-5 rounded-full"/>
      <USkeleton class="h-5 w-24"/>
    </div>
    <div class="flex flex-col space-y-2">
      <div class="flex flex-row items-center gap-1.5 py-1.5">
        <USkeleton class="h-5 w-5 rounded-full"/>
        <USkeleton class="h-5 flex-1"/>
      </div>
      <div class="flex flex-row items-center gap-1.5">
        <USkeleton class="h-5 w-5 rounded-full"/>
        <USkeleton class="h-4 w-7/12"/>
      </div>
      <div class="flex flex-row items-center gap-1.5">
        <USkeleton class="h-5 w-5 rounded-full"/>
        <USkeleton class="h-4 flex-1"/>
      </div>
      <div class="grid grid-cols-2 gap-2 w-full">
        <div class="flex flex-row items-center gap-1.5">
          <USkeleton class="h-5 w-5 rounded-full"/>
          <USkeleton class="h-4 w-9/12"/>
        </div>
        <div class="flex flex-row items-center gap-1.5">
          <USkeleton class="h-5 w-5 rounded-full"/>
          <USkeleton class="h-4 w-7/12"/>
        </div>
        <div class="flex flex-row items-center gap-1.5">
          <USkeleton class="h-5 w-5 rounded-full"/>
          <USkeleton class="h-4 w-7/12"/>
        </div>
        <div class="flex flex-row items-center gap-1.5">
          <USkeleton class="h-5 w-5 rounded-full"/>
          <USkeleton class="h-4 w-6/12"/>
        </div>
        <div class="flex flex-row items-center gap-1.5">
          <USkeleton class="h-5 w-5 rounded-full"/>
          <USkeleton class="h-4 w-3/12"/>
        </div>
        <div class="flex flex-row items-center gap-1.5">
          <USkeleton class="h-5 w-5 rounded-full"/>
          <USkeleton class="h-4 w-7/12"/>
        </div>
      </div>
    </div>
  </div>
  <UTabs
      v-else
      :items
      variant="link"
      class="gap-2 w-full">
    <template #endpoint>
      <UpdateCheck/>
      <UAccordion :items="accordionItems" :model-value="store.activeEndpoint" @update:model-value="setActive">
        <template #body="{ item }">
          <div class="space-y-2 mt-1.5">
            <template v-if="item.PublicURL">
              <a
                  v-if="checkStringType(item.PublicURL) === 'url'"
                  class="flex items-center space-x-1"
                  :href="item.PublicURL" target="_blank">
                <UIcon name="i-mdi-link-variant" class="size-5"/>
                <b>URL</b> : {{ item.PublicURL }}
              </a>
              <p v-else class="flex items-center space-x-1">
                <UIcon name="i-mdi-ip-network" class="size-5"/>
                <b>IP</b> : {{ item.PublicURL }}
              </p>
            </template>
            <p class="flex items-center space-x-1">
              <UIcon name="i-ix-operating-system" class="size-5"/>
              <b>OS</b> : {{ item.OperatingSystem }} ( {{ item.OSType }} {{ item.Architecture }} )
            </p>
            <div class="grid grid-cols-2 gap-2">
              <UTooltip text="CPU cores">
                <p class="flex items-center space-x-1">
                  <UIcon name="i-ph-cpu-bold" class="size-5"/>
                  <span> : {{ item.TotalCPU }} Cores</span></p>
              </UTooltip>
              <UTooltip text="Memory">
                <p class="flex items-center space-x-1">
                  <UIcon name="i-bi-memory" class="size-5"/>
                  <span> : {{ item.TotalMemory }}</span></p>
              </UTooltip>
              <UTooltip text="Docker Version">
                <p class="flex items-center space-x-1">
                  <UIcon name="i-mdi-docker" class="size-5"/>
                  <span> : {{ item.DockerVersion }}</span></p>
              </UTooltip>
              <UTooltip :text="JSON.parse(item.Gpus).join(', ')">
                <p class="flex items-center space-x-1">
                  <UIcon name="i-ph-graphics-card-bold" class="size-5"/>
                  <span> : {{ JSON.parse(item.Gpus).length }} Gpus</span></p>
              </UTooltip>
              <UTooltip text="Stacks">
                <p class="flex items-center space-x-1">
                  <UIcon name="i-mdi-layers-triple-outline" class="size-5"/>
                  <span> : {{ item.StackCount }}</span></p>
              </UTooltip>
              <UTooltip text="Running Containers / Total Containers">
                <p class="flex items-center space-x-1">
                  <UIcon name="i-mdi-cube-outline" class="size-5"/>
                  <span> : {{ item.RunningContainerCount }} / {{ item.ContainerCount }}</span></p>
              </UTooltip>
            </div>
          </div>
        </template>
      </UAccordion>
    </template>
  </UTabs>

</template>
