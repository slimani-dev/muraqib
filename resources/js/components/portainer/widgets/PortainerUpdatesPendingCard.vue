<script setup lang="ts">
import StatWidget from '@/components/dashboard/StatWidget.vue';
// Logic will be implemented later
</script>

<template>
  <StatWidget
    title="Updates Pending"
    icon="upgrade"
    icon-color="text-yellow-600 dark:text-yellow-500"
    value="4"
    subtitle="New image versions detected"
    variant="warning"
    subtitleClass="font-mono"
  >
    <template #actions>
      <button class="text-slate-400 transition-colors hover:text-primary">
        <span class="material-symbols-outlined text-lg">refresh</span>
      </button>
    </template>
  </StatWidget>
</template>
