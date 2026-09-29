<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';

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
    router.post(
        `/api/containers/${props.c.id}/check-update`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                checkingUpdateFor.value = null;
            },
        },
    );
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

/** Docker's health check result, shown as an icon under the logo instead of in the status line. */
const getHealthBadge = (status: string) => {
    const s = (status || '').toLowerCase();

    if (s.includes('(healthy)')) {
        return {
            label: 'Healthy',
            icon: 'lucide:heart-pulse',
            color: 'text-emerald-500',
        };
    }

    if (s.includes('(unhealthy)')) {
        return {
            label: 'Unhealthy',
            icon: 'lucide:heart-crack',
            color: 'text-destructive',
        };
    }

    if (s.includes('health: starting')) {
        return {
            label: 'Starting',
            icon: 'lucide:loader-2',
            color: 'text-warning animate-spin',
        };
    }

    return null;
};

/** The uptime part of Docker's status ("Up 10 minutes"), without the health suffix. */
const getUptimeInfo = (status: string) => {
    const text = (status || '')
        .replace(/\s*\((healthy|unhealthy|health: starting)\)\s*/i, ' ')
        .trim();
    const s = text.toLowerCase();

    if (s.startsWith('exited') || s.includes('dead')) {
        return { text, color: 'text-destructive', icon: 'lucide:x-circle' };
    }

    if (s.startsWith('up ')) {
        return { text, color: 'text-chart-3', icon: 'lucide:clock' };
    }

    return { text, color: 'text-muted-foreground', icon: 'lucide:activity' };
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
        minute: '2-digit',
    });
};
</script>

<template>
    <component
        :is="c.url ? 'a' : 'div'"
        :href="c.url"
        :target="c.url ? '_blank' : undefined"
        :rel="c.url ? 'noreferrer' : undefined"
        class="relative flex flex-col gap-2 p-3 transition-all hover:bg-muted/40 widget-md:rounded-xl widget-md:border widget-md:bg-card/70 widget-md:shadow-sm widget-md:backdrop-blur-xl widget-md:hover:bg-card/90 widget-md:hover:shadow-md"
        :class="c.url ? 'group cursor-pointer' : ''"
    >
        <!-- Identity: logo, name + stack, description -->
        <div class="flex min-w-0 items-center gap-3 pr-5">
            <div
                class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg border bg-muted/50"
            >
                <img
                    v-if="c.icon"
                    :src="c.icon"
                    :alt="c.name"
                    class="h-6 w-6 object-contain"
                />
                <Icon
                    v-else
                    icon="lucide:container"
                    class="h-5 w-5 text-muted-foreground"
                />
            </div>
            <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                <div class="flex min-w-0 items-center gap-1.5">
                    <span
                        class="block truncate text-[13px] font-bold text-foreground transition-colors"
                        :class="c.url ? 'group-hover:text-primary' : ''"
                    >
                        {{ c.display_name || c.name }}
                    </span>
                    <span
                        v-if="c.stack_name"
                        class="shrink-0 rounded-sm border border-border/50 bg-muted/30 px-1 py-0.5 text-[9px] leading-none tracking-wider text-muted-foreground uppercase"
                    >
                        {{ c.stack_name }}
                    </span>
                </div>
                <span
                    class="block truncate text-[11px] text-muted-foreground"
                    :title="c.description || c.image"
                >
                    {{ c.description || c.image }}
                </span>
            </div>
        </div>

        <!-- Status: health (under the logo), update, uptime -->
        <div
            class="grid min-w-0 grid-cols-[2.5rem_1fr_1fr] items-center gap-x-3 whitespace-nowrap"
        >
            <div class="flex justify-center">
                <Icon
                    v-if="getHealthBadge(c.status)"
                    :icon="getHealthBadge(c.status)!.icon"
                    class="h-3.5 w-3.5"
                    :class="getHealthBadge(c.status)!.color"
                    :title="getHealthBadge(c.status)!.label"
                />
            </div>
            <!-- Update Status Line -->
            <button
                @click="(e) => checkUpdate(e)"
                :disabled="checkingUpdateFor === c.id"
                class="flex min-h-[14px] min-w-0 cursor-pointer items-center gap-1.5 text-left transition-opacity hover:opacity-80 disabled:cursor-default disabled:opacity-50"
            >
                <template v-if="checkingUpdateFor === c.id">
                    <div
                        class="flex items-center gap-1 text-[10px] font-medium text-primary"
                    >
                        <Icon
                            icon="lucide:refresh-cw"
                            class="h-3 w-3 animate-spin"
                        />
                        Checking...
                    </div>
                </template>
                <template v-else-if="c.update_status === 'up_to_date'">
                    <div
                        class="flex items-center gap-1 text-[10px] font-medium text-emerald-500"
                    >
                        <Icon icon="lucide:check-circle-2" class="h-3 w-3" />
                        Up to date
                    </div>
                </template>
                <template v-else-if="c.update_status === 'unknown'">
                    <div
                        class="flex items-center gap-1 text-[10px] font-medium text-muted-foreground"
                    >
                        <Icon icon="lucide:help-circle" class="h-3 w-3" />
                        Unknown
                    </div>
                </template>
                <template v-else-if="c.update_status === 'error'">
                    <div
                        class="flex items-center gap-1 text-[10px] font-medium text-destructive"
                        :title="c.update_error"
                    >
                        <Icon icon="lucide:alert-circle" class="h-3 w-3" />
                        Update Error
                    </div>
                </template>
                <template v-else-if="c.update_status === 'update_available'">
                    <TooltipProvider>
                        <Tooltip :delay-duration="200">
                            <TooltipTrigger asChild>
                                <div
                                    class="flex items-center gap-1.5 text-[10px] font-bold text-chart-4"
                                >
                                    <Icon
                                        icon="lucide:arrow-up-circle"
                                        class="h-3 w-3"
                                    />
                                    <span>Update Available</span>
                                </div>
                            </TooltipTrigger>
                            <TooltipContent
                                side="top"
                                class="min-w-[200px] p-3 text-xs"
                                @click.stop
                            >
                                <div class="mb-2 font-semibold">
                                    Update Information
                                </div>
                                <div
                                    class="grid grid-cols-[60px_1fr] gap-x-2 gap-y-1.5 text-[11px]"
                                >
                                    <span class="text-muted-foreground"
                                        >Current:</span
                                    >
                                    <span class="font-medium">{{
                                        c.current_release || '—'
                                    }}</span>

                                    <span class="text-muted-foreground"
                                        >Digest:</span
                                    >
                                    <span class="font-mono text-[10px]">{{
                                        formatDigest(c.latest_digest)
                                    }}</span>

                                    <template v-if="c.available_tags?.length">
                                        <span class="text-muted-foreground"
                                            >Tags:</span
                                        >
                                        <div class="flex flex-wrap gap-1">
                                            <span
                                                v-for="tag in c.available_tags.slice(
                                                    0,
                                                    3,
                                                )"
                                                :key="tag"
                                                class="rounded-[4px] bg-secondary px-1 py-0.5 font-mono text-[9px] text-secondary-foreground"
                                                >{{ tag }}</span
                                            >
                                            <span
                                                v-if="
                                                    c.available_tags.length > 3
                                                "
                                                class="ml-0.5 self-center text-[9px] text-muted-foreground"
                                            >
                                                +{{
                                                    c.available_tags.length - 3
                                                }}
                                            </span>
                                        </div>
                                    </template>

                                    <span class="text-muted-foreground"
                                        >Checked:</span
                                    >
                                    <span class="text-muted-foreground">{{
                                        formatDate(c.update_checked_at)
                                    }}</span>
                                </div>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </template>
                <template v-else>
                    <div
                        class="flex items-center gap-1 text-[10px] font-medium text-muted-foreground"
                    >
                        <Icon icon="lucide:help-circle" class="h-3 w-3" />
                        Check Update
                    </div>
                </template>
            </button>

            <!-- Uptime -->
            <div
                v-if="c.status"
                class="flex min-w-0 items-center gap-1 border-l border-border/50 pl-3 text-[10px] font-medium"
                :class="getUptimeInfo(c.status).color"
            >
                <Icon
                    :icon="getUptimeInfo(c.status).icon"
                    class="h-3 w-3 shrink-0"
                />
                {{ getUptimeInfo(c.status).text }}
            </div>
            <span v-else></span>
        </div>

        <div class="absolute top-3 right-3 flex items-center justify-center">
            <template v-if="c.url && c.state === 'running'">
                <span
                    v-if="reachable === true"
                    class="relative flex h-2.5 w-2.5"
                    title="Reachable"
                >
                    <span
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-chart-3 opacity-75"
                    ></span>
                    <span
                        class="relative inline-flex h-2.5 w-2.5 rounded-full bg-chart-3"
                    ></span>
                </span>
                <span
                    v-else-if="reachable === false"
                    class="relative flex h-2.5 w-2.5"
                    title="Unreachable"
                >
                    <span
                        class="relative inline-flex h-2.5 w-2.5 rounded-full bg-destructive"
                    ></span>
                </span>
                <span v-else class="relative flex h-2 w-2" title="Checking...">
                    <span
                        class="relative inline-flex h-2 w-2 animate-pulse rounded-full bg-muted-foreground"
                    ></span>
                </span>
            </template>
            <template v-else>
                <Icon
                    :icon="getStatusIcon(c.state)"
                    class="h-3.5 w-3.5"
                    :class="getStatusColor(c.status, c.state)"
                />
            </template>
        </div>
    </component>
</template>
