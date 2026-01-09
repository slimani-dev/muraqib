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
  data: { running: number; stopped: number; other: number; total: number };
}>();

const chartData = computed(() => [
  { status: 'running', count: props.data.running, fill: 'var(--color-running)' },
  { status: 'stopped', count: props.data.stopped, fill: 'var(--color-stopped)' },
  { status: 'other', count: props.data.other, fill: 'var(--color-other)' },
]);

const chartConfig = {
  running: {
    label: 'Running',
    color: 'hsl(var(--success))',
  },
  stopped: {
    label: 'Stopped',
    color: 'hsl(var(--destructive))',
  },
  other: {
    label: 'Other',
    color: 'hsl(var(--muted-foreground))',
  },
} satisfies ChartConfig;

const filteredData = computed(() => chartData.value.filter((d) => d.count > 0));
const totalContainers = computed(() => props.data.total.toString());
const runningContainers = computed(() => props.data.running.toString());
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
        :color="(d: any) => (d.status === 'running' ? '#22c55e' : d.status === 'stopped' ? '#ef4444' : '#94a3b8')"
        :arc-width="20"
        :central-label-offset-y="2"
        :central-label="`${runningContainers}/${totalContainers}`"
      />
      <ChartTooltip
        :triggers="{
          [Donut.selectors.segment]: componentToString(chartConfig, ChartTooltipContent, { hideLabel: true })!,
        }"
      />
    </VisSingleContainer>
  </ChartContainer>
</template>
