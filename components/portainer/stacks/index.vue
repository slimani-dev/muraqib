<script setup lang="ts">
import type {Stack} from "~/types";
import Home from "~/components/portainer/stacks/Home.vue";
import StackInfo from "~/components/portainer/stacks/StackInfo.vue";

const items = [
  {
    label: 'Home',
    slot: 'home',
    icon: 'i-mdi-home'
  },
]

const {status: stacksStatus, data: stacks} = useFetch<Stack[]>('/api/portainer/stacks/', {
  server: false,
  lazy: true
})

const tabItems = computed(() =>
    ([
      ...items,
      ...(stacks.value?.map((stack) => ({
        ...stack,
        slot: stack.label.toLowerCase()
      })) || [])
    ])
)
</script>


<template>
  <div v-if="stacksStatus === 'pending' || stacksStatus === 'idle'" class="grid grid-cols-1 gap-2">
    <div class="flex flex-row px-4 py-3.5 gap-6">
      <div v-for="i in [1,2,3,4]" :key="i" class="flex flex-row gap-1.5">
        <USkeleton class="h-5 w-5 rounded-full"/>
        <USkeleton class="h-5 w-24"/>
      </div>
    </div>
    <div class="flex flex-col space-y-2">
      <div class="flex flex-row items-center space-x-1.5 py-1.5">
        <USkeleton class="h-5 w-5 rounded-full"/>
        <USkeleton class="h-5 flex-1"/>
      </div>
      <div class="flex flex-row items-center space-x-1.5">
        <USkeleton class="h-5 w-5 rounded-full"/>
        <USkeleton class="h-4 w-7/12"/>
      </div>
      <div class="flex flex-row items-center space-x-1.5">
        <USkeleton class="h-5 w-5 rounded-full"/>
        <USkeleton class="h-4 flex-1"/>
      </div>
      <div class="grid grid-cols-2 gap-2 w-full">
        <div class="flex flex-row items-center space-x-1.5">
          <USkeleton class="h-5 w-5 rounded-full"/>
          <USkeleton class="h-4 w-9/12"/>
        </div>
        <div class="flex flex-row items-center space-x-1.5">
          <USkeleton class="h-5 w-5 rounded-full"/>
          <USkeleton class="h-4 w-7/12"/>
        </div>
        <div class="flex flex-row items-center space-x-1.5">
          <USkeleton class="h-5 w-5 rounded-full"/>
          <USkeleton class="h-4 w-7/12"/>
        </div>
        <div class="flex flex-row items-center space-x-1.5">
          <USkeleton class="h-5 w-5 rounded-full"/>
          <USkeleton class="h-4 w-6/12"/>
        </div>
        <div class="flex flex-row items-center space-x-1.5">
          <USkeleton class="h-5 w-5 rounded-full"/>
          <USkeleton class="h-4 w-3/12"/>
        </div>
        <div class="flex flex-row items-center space-x-1.5">
          <USkeleton class="h-5 w-5 rounded-full"/>
          <USkeleton class="h-4 w-7/12"/>
        </div>
      </div>
    </div>
  </div>

  <!-- TODO get rid of the 1 px gap between the indicator and the bottom border of the list-->
  <UTabs
      v-else
      :items="tabItems"
      variant="link"
      class="gap-2 max-w-full"
      :ui="{
        indicator: 'bottom-[1px]',
        list: 'overflow-x-auto'
      }">
    <template v-for="tabItem in tabItems" :key="tabItem.slot" #[tabItem.slot]="{ item }">
      <div class="flex flex-row space-y-2 py-3.5 -mt-[1px] font-medium text-sm gap-1.5">
        <UIcon :name="item.icon" class="size-5"/>
        <h3 class="">{{ item.label }}</h3>
      </div>

      <Home v-if="item.slot === 'home'"/>
      <StackInfo v-else :stack="item"/>
    </template>
  </UTabs>


</template>

<style scoped>

</style>
