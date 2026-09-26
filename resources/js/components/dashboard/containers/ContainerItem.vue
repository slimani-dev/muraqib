<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

const props = defineProps<{
    c: any;
    reachable?: boolean | null;
}>();

const checkingUpdateFor = ref<number | null>(null);

const checkUpdate = (e: Event) => {
    e.preventDefault();
    e.stopPropagation();

    if (checkingUpdateFor.value) {
return;
}

    checkingUpdateFor.value = props.c.id;
    router.post(`/api/containers/${props.c.id}/check-update`, {}, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            checkingUpdateFor.value = null;
        }
    });
};

const getStatusColor = (status: string, state: string) => {
    if (state === 'running') {
return 'text-chart-3';
}

    if (state === 'exited' || state === 'dead') {
return 'text-destructive';
}

    if (state === 'paused') {
return 'text-warning';
}

    return 'text-muted-foreground';
};

const getStatusIcon = (state: string) => {
    if (state === 'running') {
return 'lucide:check-circle-2';
}

    if (state === 'exited' || state === 'dead') {
return 'lucide:x-circle';
}

    if (state === 'paused') {
return 'lucide:pause-circle';
}

    return 'lucide:help-circle';
};

const getHealthInfo = (status: string) => {
    if (!status) {
return { text: '', color: 'text-muted-foreground', icon: 'lucide:activity' };
}

    let color = 'text-muted-foreground';
    let icon = 'lucide:activity';
    const s = status.toLowerCase();

    if (s.includes('(healthy)')) {
        color = 'text-emerald-500';
        icon = 'lucide:heart-pulse';
    } else if (s.includes('(unhealthy)')) {
        color = 'text-destructive';
        icon = 'lucide:heart-crack';
    } else if (s.includes('starting')) {
        color = 'text-warning';
        icon = 'lucide:loader-2';
    } else if (s.startsWith('up ')) {
        color = 'text-chart-3';
        icon = 'lucide:clock';
    } else if (s.startsWith('exited') || s.includes('dead')) {
        color = 'text-destructive';
        icon = 'lucide:x-circle';
    }

    return { text: status, color, icon };
};

const formatDigest = (digest?: string) => {
    if (!digest) {
return '—';
}

    const sha = digest.startsWith('sha256:') ? digest.substring(7) : digest;

    return sha.substring(0, 12);
};

const formatDate = (dateString?: string) => {
    if (!dateString) {
return '—';
}

    return new Date(dateString).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <component :is="c.url ? 'a' : 'div'" :href="c.url" :target="c.url ? '_blank' : undefined" :rel="c.url ? 'noreferrer' : undefined"
        class="flex items-center gap-2 rounded-xl border bg-card/70 backdrop-blur-xl p-3 shadow-sm hover:shadow-md transition-all hover:bg-card/90"
        :class="c.url ? 'cursor-pointer group' : ''">
        <div class="shrink-0 flex items-center justify-center h-10 w-10 rounded-lg bg-muted/50 border overflow-hidden">
            <img v-if="c.icon" :src="c.icon" :alt="c.name" class="w-6 h-6 object-contain" />
            <Icon v-else icon="lucide:container" class="h-5 w-5 text-muted-foreground" />
        </div>
        <div class="min-w-0 flex-1 flex flex-col justify-center">
            <div class="flex items-center gap-1.5 w-full">
                <span class="text-[13px] font-bold text-foreground transition-colors truncate block"
                    :class="c.url ? 'group-hover:text-primary' : ''">
                    {{ c.display_name || c.name }}
                </span>
                <span v-if="c.stack_name"
                    class="shrink-0 text-[9px] border border-border/50 px-1 py-0.5 rounded-sm bg-muted/30 text-muted-foreground uppercase tracking-wider leading-none">
                    {{ c.stack_name }}
                </span>
            </div>
            <div class="flex flex-col mt-0.5 space-y-1">
                <span class="text-[11px] text-muted-foreground truncate block"
                    :title="c.description || c.image">
                    {{ c.description || c.image }}
                </span>
                <div class="flex items-center gap-3 flex-wrap mt-0.5">
                    <!-- Update Status Line -->
                    <button @click="(e) => checkUpdate(e)" :disabled="checkingUpdateFor === c.id" class="flex items-center gap-1.5 min-h-[14px] text-left hover:opacity-80 transition-opacity disabled:opacity-50 cursor-pointer disabled:cursor-default">
                        <template v-if="checkingUpdateFor === c.id">
                            <div class="flex items-center gap-1 text-[10px] text-primary font-medium">
                                <Icon icon="lucide:refresh-cw" class="w-3 h-3 animate-spin" />
                                Checking...
                            </div>
                        </template>
                        <template v-else-if="c.update_status === 'up_to_date'">
                            <div class="flex items-center gap-1 text-[10px] text-emerald-500 font-medium">
                                <Icon icon="lucide:check-circle-2" class="w-3 h-3" />
                                Up to date
                            </div>
                        </template>
                        <template v-else-if="c.update_status === 'unknown'">
                            <div class="flex items-center gap-1 text-[10px] text-muted-foreground font-medium">
                                <Icon icon="lucide:help-circle" class="w-3 h-3" />
                                Unknown
                            </div>
                        </template>
                        <template v-else-if="c.update_status === 'error'">
                            <div class="flex items-center gap-1 text-[10px] text-destructive font-medium" :title="c.update_error">
                                <Icon icon="lucide:alert-circle" class="w-3 h-3" />
                                Update Error
                            </div>
                        </template>
                        <template v-else-if="c.update_status === 'update_available'">
                            <TooltipProvider>
                                <Tooltip :delay-duration="200">
                                    <TooltipTrigger asChild>
                                        <div class="flex items-center gap-1.5 text-[10px] text-chart-4 font-bold">
                                            <Icon icon="lucide:arrow-up-circle" class="w-3 h-3" />
                                            <span>Update Available</span>
                                        </div>
                                    </TooltipTrigger>
                                    <TooltipContent side="top" class="text-xs p-3 min-w-[200px]" @click.stop>
                                        <div class="font-semibold mb-2">Update Information</div>
                                        <div class="grid grid-cols-[60px_1fr] gap-x-2 gap-y-1.5 text-[11px]">
                                            <span class="text-muted-foreground">Current:</span>
                                            <span class="font-medium">{{ c.current_release || '—' }}</span>

                                            <span class="text-muted-foreground">Digest:</span>
                                            <span class="font-mono text-[10px]">{{ formatDigest(c.latest_digest) }}</span>

                                            <template v-if="c.available_tags?.length">
                                                <span class="text-muted-foreground">Tags:</span>
                                                <div class="flex flex-wrap gap-1">
                                                    <span v-for="tag in c.available_tags.slice(0, 3)" :key="tag" class="bg-secondary text-secondary-foreground px-1 py-0.5 rounded-[4px] text-[9px] font-mono">{{ tag }}</span>
                                                    <span v-if="c.available_tags.length > 3" class="text-muted-foreground text-[9px] self-center ml-0.5">
                                                        +{{ c.available_tags.length - 3 }}
                                                    </span>
                                                </div>
                                            </template>

                                            <span class="text-muted-foreground">Checked:</span>
                                            <span class="text-muted-foreground">{{ formatDate(c.update_checked_at) }}</span>
                                        </div>
                                    </TooltipContent>
                                </Tooltip>
                            </TooltipProvider>
                        </template>
                        <template v-else>
                            <div class="flex items-center gap-1 text-[10px] text-muted-foreground font-medium">
                                <Icon icon="lucide:help-circle" class="w-3 h-3" />
                                Check Update
                            </div>
                        </template>
                    </button>

                    <!-- Uptime / Health Status Line -->
                    <div v-if="c.status" class="flex items-center gap-1 text-[10px] font-medium border-l pl-3 border-border/50" :class="getHealthInfo(c.status).color">
                        <Icon :icon="getHealthInfo(c.status).icon" class="w-3 h-3" :class="{'animate-spin': c.status.includes('starting')}" />
                        {{ getHealthInfo(c.status).text }}
                    </div>
                </div>
            </div>
        </div>
        <div class="shrink-0 pl-2 flex items-center justify-center min-w-[16px]">
            <template v-if="c.url && c.state === 'running'">
                <span v-if="reachable === true" class="relative flex h-2.5 w-2.5" title="Reachable">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-chart-3 opacity-75"></span>
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-chart-3"></span>
                </span>
                <span v-else-if="reachable === false" class="relative flex h-2.5 w-2.5" title="Unreachable">
                    <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-destructive"></span>
                </span>
                <span v-else class="relative flex h-2 w-2" title="Checking...">
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-muted-foreground animate-pulse"></span>
                </span>
            </template>
            <template v-else>
                <Icon :icon="getStatusIcon(c.state)" class="h-4 w-4"
                    :class="getStatusColor(c.status, c.state)" />
            </template>
        </div>
    </component>
</template>
