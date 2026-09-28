<script setup lang="ts">
import { Icon } from '@iconify/vue';
import type { WidgetItem } from '../layout';
import type { WidgetDefinition } from '../widgets/registry';
import type { WidgetMode } from '../widgets/widgetMode';
import { useLayoutEditor } from './layoutEditor';

defineProps<{
    item: WidgetItem;
    definition: WidgetDefinition | undefined;
    mode: WidgetMode;
}>();

const editor = useLayoutEditor();
</script>

<template>
    <component :is="definition.component" v-if="definition" v-bind="definition.props(editor.data.value)" :mode="mode" class="h-full" />
    <div v-else-if="editor.editing.value" class="flex items-center gap-2 rounded-lg border border-dashed p-3 text-xs text-muted-foreground">
        <Icon icon="lucide:circle-off" class="h-4 w-4" />
        This widget no longer exists (its service may have been removed).
    </div>
</template>
