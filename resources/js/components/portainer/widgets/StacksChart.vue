<script setup lang="ts">
import {
  ChartContainer,
  ChartTooltip,
  ChartTooltipContent,
  componentToString,
  type ChartConfig,
} from '@/components/ui/chart';
import { Donut } from '@unovis/ts';
import { VisDonut, VisSingleContainer } from '@unovis/vue';
import { computed } from 'vue';

const props = defineProps<{
  data: { active: number; inactive: number; total: number };
}>();

const chartData = computed(() => [
  { status: 'active', count: props.data.active, fill: 'var(--color-primary)' },
  { status: 'inactive', count: props.data.inactive, fill: 'var(--muted)' },
]);

const chartConfig = {
  active: {
    label: 'Active',
    color: 'hsl(var(--primary))',
  },
  inactive: {
    label: 'Inactive',
    color: 'hsl(var(--muted))',
  },
} satisfies ChartConfig;

const filteredData = computed(() => chartData.value.filter((d) => d.count > 0));
const totalStacks = computed(() => props.data.total.toString());
const activeStacks = computed(() => props.data.active.toString());
</script>

<template>
  <ChartContainer
    :config="chartConfig"
    class="mx-auto aspect-square h-full"
    :style="{
      '--vis-donut-central-label-font-size': '1rem',
      '--vis-donut-central-label-font-weight': 'bold',
      '--vis-donut-central-label-text-color': 'var(--foreground)',
      '--vis-donut-central-sub-label-text-color': 'var(--muted-foreground)',
    }"
  >
    <VisSingleContainer :data="filteredData" :margin="{ top: 0, bottom: 0 }">
      <VisDonut
        :value="(d: any) => d.count"
        :color="(d: any) => (d.status === 'active' ? '#6764f2' : '#ef4444')"
        :arc-width="20"
        :central-label-offset-y="2"
        :central-label="`${activeStacks}/${totalStacks}`"
      />
      <ChartTooltip
        :triggers="{
          [Donut.selectors.segment]: componentToString(chartConfig, ChartTooltipContent, { hideLabel: true })!,
        }"
      />
    </VisSingleContainer>
  </ChartContainer>
</template>
