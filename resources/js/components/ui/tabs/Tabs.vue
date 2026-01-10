<script setup lang="ts">
import { type HTMLAttributes, computed } from 'vue'
import { TabsRoot, type TabsRootEmits, type TabsRootProps, useForwardPropsEmits } from 'reka-ui'
import { cn } from '@/lib/utils'

const props = defineProps<TabsRootProps & { class?: HTMLAttributes['class'] }>()
const emits = defineEmits<TabsRootEmits>()

const delegatedProps = computed(() => {
  const { class: _, ...delegated } = props

  return delegated
})

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <TabsRoot v-bind="forwarded" :class="cn(props.class)">
    <slot />
  </TabsRoot>
</template>
