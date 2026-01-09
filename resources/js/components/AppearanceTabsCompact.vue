<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun } from 'lucide-vue-next';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
  { value: 'light', Icon: Sun, label: 'Light' },
  { value: 'dark', Icon: Moon, label: 'Dark' },
  { value: 'system', Icon: Monitor, label: 'System' },
] as const;
</script>

<template>
  <div class="flex justify-between">
    <button
      v-for="{ value, Icon, label } in tabs"
      :key="value"
      type="button"
      @click.prevent="updateAppearance(value)"
      :class="[
        // 1. FIXED: Added 'overflow-hidden' to button so rounded corners don't clip weirdly
        // 2. LOGIC: Use 'flex-[2]' for active to give it 2x space, 'flex-1' for others
        'flex items-center justify-center overflow-hidden rounded-md transition-all duration-300',
        appearance === value
          ? 'flex-[2] bg-neutral-200 px-3.5 py-1 shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
          : 'flex-1 px-1 py-1 text-neutral-500 hover:text-black dark:text-neutral-400 dark:hover:text-white',
      ]"
    >
      <component
        :is="Icon"
        :class="[
          'h-4 w-4 shrink-0 transition-all duration-300',
          // Optional: Add a slight margin right to icon when active for better spacing
          appearance === value ? '-ml-1' : '',
        ]"
      />

      <span
        class="grid transition-[grid-template-columns] duration-300 ease-out"
        :class="appearance === value ? 'grid-cols-[1fr]' : 'grid-cols-[0fr]'"
      >
        <span
          class="overflow-hidden whitespace-nowrap transition-all"
          :class="[
            appearance === value
              ? 'ml-1.5 opacity-100 delay-75 duration-300'
              : 'ml-0 opacity-0 duration-0',
          ]"
        >
          {{ label }}
        </span>
      </span>
    </button>
  </div>
</template>
