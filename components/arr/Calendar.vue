<script setup lang="ts">
const items = [
  {label: 'Calendar', icon: 'i-lucide-calendar-days', slot: 'calendar'}
]
</script>

<template>
  <UTabs
      :items
      variant="link"
      class="gap-2 w-full">
    <template #calendar>
      <UCalendar class="mt-2"/>
    </template>
  </UTabs>
</template>
