<script setup lang="ts">
import { ChartContainer, ChartTooltip, type ChartConfig } from '@/components/ui/chart';
import { VisArea, VisLine, VisXYContainer } from '@unovis/vue';

defineProps<{
  color: string;
  data: { date: number; latency: number }[];
}>();

const chartConfig = {
  latency: {
    label: 'Latency',
    color: 'hsl(var(--primary))',
  },
} satisfies ChartConfig;

const xProp = (d: { date: number }) => d.date;
const yProp = (d: { latency: number }) => d.latency;

const svgDefs = `
  <linearGradient id="latencyGradient" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0%" stop-color="hsl(var(--chart-1))" stop-opacity="0.6" />
    <stop offset="100%" stop-color="hsl(var(--chart-2))" stop-opacity="0" />
  </linearGradient>
`;
</script>

<template>
  <ChartContainer :config="chartConfig"
    class="h-24 w-full opacity-30 transition-opacity duration-300 group-hover/stat:opacity-100">
    <VisXYContainer :data="data" :margin="{ top: 0, bottom: 0, left: 0, right: 0 }" :x-domain-key="'date'"
      :y-domain-key="'latency'" :height="96" :svg-defs="svgDefs">
      <VisArea :x="xProp" :y="yProp" color="url(#latencyGradient)" />
      <VisLine :x="xProp" :y="yProp" :color="color" :lineWidth="0.5" :opacity="0.4" />
      <ChartTooltip />
    </VisXYContainer>
  </ChartContainer>
</template>
