<script setup lang="ts">

import type {ContainerInfo} from "~/types";

defineProps<{
  container: ContainerInfo
}>()

const open = ref(false)

</script>

<template>
  <UCollapsible
      v-model:open="open"
      class="w-full h-full cursor-pointer rounded-[calc(var(--ui-radius)*2)] bg-(--ui-bg) ring ring-(--ui-border) px-4 py-2"
  >
    <div class="flex flex-col gap-2">
      <div class="flex flex-row space-x-2 items-center">
        <a :href="container.muraqib_url" target="_blank">
          <img
              :src="container.muraqib_icon"
              :alt="container.name.charAt(0).toUpperCase()"
              class="size-8 object-contain">
        </a>

        <div class="flex flex-col grow pt-1">
          <h3 class="text-lg font-bold leading-4 capitalize">{{
              container.name
            }} </h3>
          <h4 class="text-(--ui-text-muted) text-xs">{{ container.stack }}</h4>
        </div>

        <UButton
            color="neutral"
            variant="link"
            icon="i-akar-icons-link-out"
            :to="container.muraqib_url"
            target="_blank"
            @click.stop
        />
      </div>
      <p
          :title="container.muraqib_description"
          class="text-sm"
          :class="{'line-clamp-1': !open}">{{ container.muraqib_description }}</p>
    </div>

    <template #content>
      <div class="flex flex-col gap-2 py-2 text-sm">
        <UTooltip :text="container.image">
          <p class="flex items-center space-x-1">
            <UIcon name="i-mdi-package-variant-closed" class="size-5"/>
            <span class="overflow-hidden text-nowrap"> {{ container.image }}</span></p>
        </UTooltip>
        <UTooltip text="state">
          <p class="flex items-center space-x-1">
            <UIcon name="mdi-progress-clock" class="size-5"/>
            <span> {{ container.state }}</span></p>
        </UTooltip>
        <UTooltip text="status">
          <p class="flex items-center space-x-1">
            <UIcon name="i-mdi-clock-outline" class="size-5"/>
            <span> {{ container.status }}</span></p>
        </UTooltip>
      </div>
    </template>

  </UCollapsible>
</template>

<style scoped>

</style>
