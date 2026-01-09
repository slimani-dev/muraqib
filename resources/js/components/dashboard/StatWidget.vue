<script setup lang="ts">
import WidgetTitle from '@/components/dashboard/WidgetTitle.vue';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

const props = withDefaults(
  defineProps<{
    server?: string;
    title: string;
    icon?: string;
    iconColor?: string;
    variant?: 'default' | 'warning' | 'success';
    value?: string | number;
    subtitle?: string;
    class?: string;
    subtitleClass?: string;
  }>(),
  {
    server: 'Portainer',
    variant: 'default',
  },
);

const containerClass = computed(() => {
  switch (props.variant) {
    case 'warning':
      return 'bg-yellow-50 dark:bg-yellow-500/10 border-yellow-200 dark:border-yellow-500/20 shadow-sm dark:shadow-lg hover:bg-yellow-100 dark:hover:bg-yellow-500/15';
    case 'success':
      return 'bg-green-50 dark:bg-green-500/10 border-green-200 dark:border-green-500/20 shadow-sm dark:shadow-lg hover:bg-green-100 dark:hover:bg-green-500/15';
    default:
      return 'bg-white dark:bg-surface-dark border-gray-200 dark:border-border-dark shadow-sm dark:shadow-lg';
  }
});

const textClass = computed(() => {
  switch (props.variant) {
    case 'warning':
      return 'text-yellow-600 dark:text-yellow-500';
    case 'success':
      return 'text-green-600 dark:text-green-500';
    default:
      return 'text-gray-900 dark:text-white';
  }
});

const subTextClass = computed(() => {
  switch (props.variant) {
    case 'warning':
      return 'text-yellow-600/80 dark:text-yellow-500/80';
    case 'success':
      return 'text-green-600/80 dark:text-green-500/80';
    default:
      return 'text-slate-500';
  }
});
</script>

<template>
  <div
    :class="
      cn(
        'group relative flex h-36 flex-col justify-start rounded-xl border p-4 transition-all',
        containerClass,
        props.class,
      )
    "
  >
    <div class="z-10 flex items-start justify-between">
      <WidgetTitle :server="props.server" :title="props.title">
        <template #icon v-if="props.icon || $slots.icon">
          <slot name="icon">
            <span :class="cn('material-symbols-outlined text-2xl', props.iconColor)">{{ props.icon }}</span>
          </slot>
        </template>
      </WidgetTitle>
      <slot name="actions"></slot>
    </div>

    <div class="z-10 flex items-center justify-between">
      <div class="flex flex-col justify-start gap-1 pt-1">
        <div class="flex h-10 max-h-10 min-h-10 flex-col items-start justify-center">
          <slot name="content">
            <span v-if="props.value" :class="cn('text-[32px] leading-8 font-bold tracking-tighter', textClass)">{{
              props.value
            }}</span>
          </slot>
        </div>
        <p v-if="props.subtitle" :class="cn('text-xs font-medium', subTextClass, props.subtitleClass)">
          {{ props.subtitle }}
        </p>
      </div>
    </div>

    <div
      class="absolute inset-0 z-0 flex items-end justify-end overflow-hidden rounded-xl"
    >
      <slot name="chart"></slot>
    </div>
  </div>
</template>
