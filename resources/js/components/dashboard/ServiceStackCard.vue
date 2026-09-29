<script setup lang="ts">
defineProps<{
    service: any;
}>();
</script>

<template>
    <div
        class="overflow-hidden rounded-2xl border bg-card/70 text-card-foreground shadow-xl backdrop-blur-xl transition-all duration-200 hover:border-primary/50"
    >
        <div
            class="flex items-center justify-between border-b border-border/10 px-4 py-3"
            :style="{ background: service.theme.base.replace(')', ', .05)') }"
        >
            <div class="flex items-center gap-2.5">
                <div
                    class="flex h-7 w-7 items-center justify-center rounded-lg border"
                    :style="{
                        background: service.theme.base.replace(')', ', .15)'),
                        borderColor: service.theme.base.replace(')', ', .25)'),
                    }"
                >
                    <i
                        :data-lucide="service.theme.icon"
                        class="h-3.5 w-3.5"
                        :class="service.theme.color"
                    ></i>
                </div>
                <div>
                    <span class="text-sm font-bold text-foreground">{{
                        service.name
                    }}</span>
                    <span
                        class="ml-2 rounded border px-1.5 py-0.5 font-mono text-[11px]"
                        :style="{
                            background: service.theme.base.replace(
                                ')',
                                ', .12)',
                            ),
                            color: service.theme.color
                                .replace('text-', 'bg-')
                                .replace('400', '300'),
                            borderColor: service.theme.base.replace(
                                ')',
                                ', .2)',
                            ),
                        }"
                        >{{ service.badge }}</span
                    >
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span
                    v-if="service.status.text"
                    :class="[
                        service.status.type === 'warn'
                            ? 'border border-chart-4/20 bg-chart-4/10 text-chart-4'
                            : 'text-chart-4',
                        'rounded-full px-2 py-0.5 text-[11px] font-semibold',
                    ]"
                    >{{ service.status.text }}</span
                >
                <span class="relative flex h-1.5 w-1.5">
                    <span
                        class="absolute inline-flex h-full w-full animate-ping rounded-full bg-chart-3 opacity-75"
                    ></span>
                    <span
                        class="relative inline-flex h-1.5 w-1.5 rounded-full bg-chart-3"
                    ></span>
                </span>
            </div>
        </div>
        <div
            :class="[
                'grid gap-0',
                service.items.length === 4
                    ? 'grid-cols-4'
                    : service.items.length === 3
                      ? 'grid-cols-3'
                      : 'grid-cols-2',
            ]"
        >
            <div
                v-for="(item, index) in service.items"
                :key="item.name"
                class="p-3.5"
                :style="{
                    borderRight:
                        (index as number) < service.items.length - 1
                            ? '1px solid var(--border)'
                            : 'none',
                }"
            >
                <div class="mb-2 flex items-center gap-1.5">
                    <img
                        v-if="item.icon && !item.iconType"
                        :src="item.icon"
                        class="h-4 w-4 rounded"
                        :class="item.iconBg"
                        alt=""
                    />
                    <i
                        v-if="item.iconType === 'lucide'"
                        :data-lucide="item.icon"
                        class="h-4 w-4"
                        :class="item.iconColor"
                    ></i>
                    <span class="text-[13px] font-bold text-foreground">{{
                        item.name
                    }}</span>
                    <span
                        v-if="item.status"
                        :class="[
                            'ml-auto rounded-full px-1.5 py-0.5 text-[11px]',
                            item.status === 'Running'
                                ? 'border border-chart-3/20 bg-chart-3/10 text-chart-3'
                                : 'border border-chart-4/20 bg-chart-4/10 text-chart-4',
                        ]"
                        >{{ item.status }}</span
                    >
                </div>
                <div v-if="item.stats" class="space-y-1 text-[12px]">
                    <div
                        v-for="stat in item.stats"
                        :key="stat.label"
                        class="flex justify-between"
                    >
                        <span class="text-muted-foreground">{{
                            stat.label
                        }}</span>
                        <span class="font-mono" :class="stat.color || ''">{{
                            stat.value
                        }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div
            v-if="service.cloudflare"
            class="flex items-center gap-3 border-t border-border/10 bg-chart-4/5 px-4 py-2.5"
        >
            <i data-lucide="cloud" class="h-4 w-4 shrink-0 text-orange-400"></i>
            <div class="flex-1 text-[12px] text-muted-foreground">
                <span class="font-semibold text-foreground"
                    >Cloudflare Tunnel</span
                >
                ·
                <span
                    class="mx-1 inline-flex items-center gap-1 rounded-full border border-chart-3/20 bg-chart-3/10 px-1.5 py-0.5 text-[11px] font-semibold text-chart-3"
                    ><span class="h-1 w-1 rounded-full bg-chart-3"></span>
                    {{ service.cloudflare.active }} active</span
                >
                · <span class="text-chart-4">⚠ Bypassed:</span>
                {{ service.cloudflare.bypassed }}
            </div>
            <div
                class="flex shrink-0 gap-3 font-mono text-[12px] text-muted-foreground"
            >
                <span>{{ service.cloudflare.blocks }}</span>
                <span class="text-chart-3">{{ service.cloudflare.ddos }}</span>
            </div>
        </div>
        <div
            v-if="service.footer || service.linkText"
            class="flex justify-between border-t border-border/10 px-4 py-2 text-[11px] text-muted-foreground"
        >
            <span>{{
                service.footer ||
                `${service.containersCount} containers · all running`
            }}</span>
            <a
                v-if="service.linkText"
                href="#"
                class="cursor-pointer text-chart-1 hover:underline"
                >{{ service.linkText }} →</a
            >
        </div>
    </div>
</template>
