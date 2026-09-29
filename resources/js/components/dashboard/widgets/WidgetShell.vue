<script setup lang="ts">
import { useElementSize } from '@vueuse/core';
import { computed, provide, useTemplateRef } from 'vue';
import type { WidgetMode } from './widgetMode';
import { resolveWidgetLayout, widgetLayoutKey } from './widgetMode';

const props = withDefaults(defineProps<{ mode?: WidgetMode }>(), {
    mode: 'desktop',
});

const shell = useTemplateRef<HTMLElement>('shell');
const { width } = useElementSize(shell);

const layout = computed<WidgetMode>(() =>
    resolveWidgetLayout(props.mode, width.value),
);

provide(widgetLayoutKey, layout);
</script>

<template>
    <div
        ref="shell"
        class="@container/widget"
        :data-widget-mode="mode"
        :data-widget-layout="layout"
    >
        <slot :layout="layout" />
    </div>
</template>
