<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '../ui/tabs';
import ContainerItem from './containers/ContainerItem.vue';
import SectionLabel from './SectionLabel.vue';
import type { WidgetMode } from './widgets/widgetMode';
import WidgetShell from './widgets/WidgetShell.vue';

const props = withDefaults(defineProps<{
    containers?: any[];
    mode?: WidgetMode;
}>(), {
    mode: 'desktop',
});

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

/** One ping at a time: each checks every container URL on the server and can take a few seconds. */
let pingInFlight = false;

const checkReachability = async () => {
    if (pingInFlight) {
        return;
    }

    pingInFlight = true;

    try {
        const response = await fetch(`/api/containers/ping`, {
            headers: { 'Accept': 'application/json' },
            signal: AbortSignal.timeout(20_000),
        });

        if (response.ok) {
            const results = await response.json();

            for (const id in results) {
                reachability.value[id] = results[id];
            }
        }
    } catch {
        // Silent fail
    } finally {
        pingInFlight = false;
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
    <WidgetShell :mode="mode" class="mb-4">
        <Tabs :model-value="activeTab" @update:model-value="handleTabChange" class="w-full">
            <div class="mb-3 flex w-full flex-col gap-2 widget-md:flex-row widget-md:items-center widget-md:justify-between">
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
                <TabsList class="grid h-8 w-full grid-cols-2 widget-md:inline-flex widget-md:w-auto">
                    <TabsTrigger value="labeled" class="text-xs px-3 h-6">Labeled</TabsTrigger>
                    <TabsTrigger value="all" class="text-xs px-3 h-6">All Containers</TabsTrigger>
                </TabsList>
            </div>

            <TabsContent value="labeled"
                class="mt-0 outline-none" :class="hasInteracted ? 'data-[state=active]:animate-in data-[state=active]:fade-in-0 data-[state=active]:zoom-in-95 duration-300' : ''">
                <div v-if="labeledContainers.length > 0" class="grid grid-cols-1 divide-y divide-border/50 overflow-hidden rounded-xl border bg-card/70 backdrop-blur-xl widget-md:grid-cols-2 widget-lg:grid-cols-3 widget-md:gap-2 widget-md:divide-y-0 widget-md:overflow-visible widget-md:rounded-none widget-md:border-0 widget-md:bg-transparent widget-md:backdrop-blur-none">
                    <ContainerItem v-for="c in labeledContainers" :key="c.container_id" :c="c" :reachable="reachability[c.container_id]" />
                </div>
                <div v-if="labeledContainers.length === 0"
                    class="text-center py-6 text-sm text-muted-foreground border rounded-xl bg-card/30 border-dashed">
                    No labeled containers found.
                </div>
            </TabsContent>

            <TabsContent value="all"
                class="mt-0 outline-none" :class="hasInteracted ? 'data-[state=active]:animate-in data-[state=active]:fade-in-0 data-[state=active]:zoom-in-95 duration-300' : ''">
                <div class="-mx-1 mb-3 flex gap-2 overflow-x-auto px-1 pb-1 [scrollbar-width:none] widget-md:flex-wrap widget-md:overflow-visible widget-md:pb-0">
                    <button v-for="stack in availableStacks" :key="stack" @click="selectedStack = stack"
                        class="shrink-0 whitespace-nowrap text-xs px-3 py-1.5 rounded-full border transition-all duration-300 font-medium"
                        :class="selectedStack === stack ? 'bg-primary text-primary-foreground border-primary shadow-md' : 'bg-card/50 text-muted-foreground border-border/50 hover:bg-card hover:text-foreground hover:border-border'">
                        {{ stack }}
                    </button>
                </div>

                <TransitionGroup name="list" tag="div"
                    class="relative grid grid-cols-1 divide-y divide-border/50 overflow-hidden rounded-xl border bg-card/70 backdrop-blur-xl widget-md:grid-cols-2 widget-lg:grid-cols-3 widget-md:gap-2 widget-md:divide-y-0 widget-md:overflow-visible widget-md:rounded-none widget-md:border-0 widget-md:bg-transparent widget-md:backdrop-blur-none">
                    <ContainerItem v-for="c in allContainers" :key="c.container_id" :c="c" :reachable="reachability[c.container_id]" />
                </TransitionGroup>
            </TabsContent>
        </Tabs>
    </WidgetShell>
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
