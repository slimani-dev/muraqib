<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../ui/tabs';
import ContainerItem from './containers/ContainerItem.vue';
import SectionLabel from './SectionLabel.vue';

const props = defineProps<{
    containers?: any[];
}>();

const labeledContainers = computed(() => {
    if (!props.containers) {
return [];
}

    return props.containers.filter(c => c.display_name || c.icon || c.url || c.is_main);
});

const portainerInfo = computed(() => {
    if (!props.containers || props.containers.length === 0) {
return null;
}

    const c = props.containers.find(c => c.portainer);

    return c ? c.portainer : null;
});

const selectedStack = ref('All');
const activeTab = ref('labeled');
const hasInteracted = ref(false);

const handleTabChange = (value: string | number) => {
    hasInteracted.value = true;
    activeTab.value = value as string;
};

const availableStacks = computed(() => {
    const stacks = new Set<string>();
    let hasUnstacked = false;
    (props.containers || []).forEach(c => {
        if (c.stack_name) {
stacks.add(c.stack_name);
} else {
hasUnstacked = true;
}
    });
    const sorted = Array.from(stacks).sort();

    if (hasUnstacked) {
sorted.push('Unstacked');
}

    return ['All', ...sorted];
});

const allContainers = computed(() => {
    const containers = props.containers || [];

    if (selectedStack.value === 'All') {
return containers;
}

    if (selectedStack.value === 'Unstacked') {
return containers.filter(c => !c.stack_name);
}

    return containers.filter(c => c.stack_name === selectedStack.value);
});

const reachability = ref<Record<string, boolean>>({});
let pingInterval: ReturnType<typeof setInterval>;

const checkReachability = async () => {
    try {
        const response = await fetch(`/api/containers/ping`, {
            headers: { 'Accept': 'application/json' }
        });
        if (response.ok) {
            const results = await response.json();
            for (const id in results) {
                reachability.value[id] = results[id];
            }
        }
    } catch (e) {
        // Silent fail
    }
};

onMounted(() => {
    checkReachability();
    pingInterval = setInterval(checkReachability, 60000);
});

onUnmounted(() => {
    if (pingInterval) {
clearInterval(pingInterval);
}
});
</script>

<template>
    <div class="mb-4">
        <Tabs :model-value="activeTab" @update:model-value="handleTabChange" class="w-full">
            <div class="flex items-center justify-between mb-3 w-full">
                <a v-if="portainerInfo" :href="portainerInfo.url" target="_blank" rel="noopener noreferrer"
                    class="flex items-center gap-2.5 group cursor-pointer">
                    <div
                        class="flex items-center justify-center w-8 h-8 group-hover:scale-105 transition-transform shrink-0">
                        <img src="https://raw.githubusercontent.com/homarr-labs/dashboard-icons/main/png/portainer-dark.png" alt="Portainer" class="w-6 h-6 object-contain drop-shadow-sm" />
                    </div>
                    <div class="flex flex-col">
                        <h2
                            class="text-sm font-bold text-foreground group-hover:text-primary transition-colors tracking-tight">
                            Docker Containers</h2>
                        <span
                            class="text-[10px] text-muted-foreground/80 -mt-0.5 uppercase tracking-wider max-w-[200px] truncate">{{
                            portainerInfo.name }}</span>
                    </div>
                </a>
                <SectionLabel v-else icon="container" text="Docker Containers" class="!mb-0 mr-4 flex-1" />
                <TabsList class="h-8">
                    <TabsTrigger value="labeled" class="text-xs px-3 h-6">Labeled</TabsTrigger>
                    <TabsTrigger value="all" class="text-xs px-3 h-6">All Containers</TabsTrigger>
                </TabsList>
            </div>

            <TabsContent value="labeled"
                class="mt-0 outline-none" :class="hasInteracted ? 'data-[state=active]:animate-in data-[state=active]:fade-in-0 data-[state=active]:zoom-in-95 duration-300' : ''">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2">
                    <ContainerItem v-for="c in labeledContainers" :key="c.container_id" :c="c" :reachable="reachability[c.container_id]" />
                </div>
                <div v-if="labeledContainers.length === 0"
                    class="text-center py-6 text-sm text-muted-foreground border rounded-xl bg-card/30 border-dashed">
                    No labeled containers found.
                </div>
            </TabsContent>

            <TabsContent value="all"
                class="mt-0 outline-none" :class="hasInteracted ? 'data-[state=active]:animate-in data-[state=active]:fade-in-0 data-[state=active]:zoom-in-95 duration-300' : ''">
                <div class="flex flex-wrap gap-2 mb-3">
                    <button v-for="stack in availableStacks" :key="stack" @click="selectedStack = stack"
                        class="text-xs px-3 py-1.5 rounded-full border transition-all duration-300 font-medium"
                        :class="selectedStack === stack ? 'bg-primary text-primary-foreground border-primary shadow-md' : 'bg-card/50 text-muted-foreground border-border/50 hover:bg-card hover:text-foreground hover:border-border'">
                        {{ stack }}
                    </button>
                </div>

                <TransitionGroup name="list" tag="div"
                    class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-2 relative">
                    <ContainerItem v-for="c in allContainers" :key="c.container_id" :c="c" :reachable="reachability[c.container_id]" />
                </TransitionGroup>
            </TabsContent>
        </Tabs>
    </div>
</template>

<style scoped>
.list-move,
.list-enter-active,
.list-leave-active {
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.list-enter-from,
.list-leave-to {
    opacity: 0;
    transform: scale(0.9) translateY(10px);
}

.list-leave-active {
    position: absolute;
    visibility: hidden;
}
</style>
