<script setup lang="ts">
import { computed, ref } from 'vue';

const props = defineProps<{
    data: number[];
    color?: string;
    width?: number;
    height?: number;
    strokeWidth?: number;
    formatter?: (val: number) => string;
}>();

const w = props.width || 100;
const h = props.height || 40;
const stroke = props.strokeWidth || 1.5;

const hoverIndex = ref<number | null>(null);

const onMouseMove = (event: MouseEvent) => {
    if (!props.data || props.data.length === 0) return;
    const rect = (event.currentTarget as HTMLElement).getBoundingClientRect();
    const x = event.clientX - rect.left;
    const percentage = x / rect.width;
    const index = Math.round(percentage * (props.data.length - 1));
    hoverIndex.value = Math.max(0, Math.min(props.data.length - 1, index));
};

const onMouseLeave = () => {
    hoverIndex.value = null;
};

const pathData = computed(() => {
    if (!props.data || props.data.length === 0) return { line: '', fill: '' };
    
    // Create a normalized array of points
    const min = Math.min(...props.data);
    const max = Math.max(...props.data);
    const range = max - min || 1;
    const stepX = w / (props.data.length - 1 || 1);
    
    const points = props.data.map((value, i) => {
        const x = i * stepX;
        const y = h - ((value - min) / range) * (h - stroke * 2) - stroke;
        return { x, y };
    });

    if (points.length < 2) return { line: '', fill: '' };

    // Catmull-Rom to Bezier curve conversion for smooth lines
    let d = `M ${points[0].x},${points[0].y}`;
    for (let i = 0; i < points.length - 1; i++) {
        const p0 = points[i === 0 ? 0 : i - 1];
        const p1 = points[i];
        const p2 = points[i + 1];
        const p3 = points[i + 2 === points.length ? i + 1 : i + 2];

        // Tension: 0.2 provides a nice, smooth curve without overshooting too much
        const tension = 0.2;
        const cp1x = p1.x + (p2.x - p0.x) * tension;
        const cp1y = p1.y + (p2.y - p0.y) * tension;
        
        const cp2x = p2.x - (p3.x - p1.x) * tension;
        const cp2y = p2.y - (p3.y - p1.y) * tension;

        d += ` C ${cp1x},${cp1y} ${cp2x},${cp2y} ${p2.x},${p2.y}`;
    }
    
    // Create fill path by closing the line path to the bottom
    const fillPath = d + ` L ${points[points.length - 1].x},${h} L ${points[0].x},${h} Z`;
    
    return { line: d, fill: fillPath };
});

const dotStyle = computed(() => {
    if (hoverIndex.value === null || !props.data || props.data.length === 0) return {};
    
    const min = Math.min(...props.data);
    const max = Math.max(...props.data);
    const range = max - min || 1;
    const value = props.data[hoverIndex.value];
    
    const y = h - ((value - min) / range) * (h - stroke * 2) - stroke;
    const topPercent = (y / h) * 100;
    const leftPercent = (hoverIndex.value / (props.data.length - 1)) * 100;
    
    return {
        left: `${leftPercent}%`,
        top: `${topPercent}%`,
        width: '6px',
        height: '6px',
        backgroundColor: props.color || 'currentColor',
        boxShadow: `0 0 4px ${props.color || 'currentColor'}`
    };
});

const tooltipStyle = computed(() => {
    if (hoverIndex.value === null || !props.data || props.data.length === 0) return {};
    const leftPercent = (hoverIndex.value / (props.data.length - 1)) * 100;
    
    // Shift translation at the edges to prevent clipping due to overflow-hidden
    let translateX = '-50%';
    if (leftPercent < 15) translateX = '0%';
    if (leftPercent > 85) translateX = '-100%';
    
    return {
        left: `${leftPercent}%`,
        top: dotStyle.value.top,
        transform: `translate(${translateX}, -150%)`
    };
});
</script>

<template>
    <div 
        class="relative w-full h-full cursor-crosshair group/sparkline"
        @mousemove="onMouseMove"
        @mouseleave="onMouseLeave"
    >
        <svg 
            :viewBox="`0 0 ${w} ${h}`" 
            preserveAspectRatio="none"
            fill="none" 
            xmlns="http://www.w3.org/2000/svg"
            class="w-full h-full"
        >
            <defs>
                <linearGradient :id="`gradient-${color?.replace('#', '')}`" x1="0" x2="0" y1="0" y2="1">
                    <stop offset="0%" :stop-color="color || 'currentColor'" stop-opacity="0.3" />
                    <stop offset="100%" :stop-color="color || 'currentColor'" stop-opacity="0.0" />
                </linearGradient>
            </defs>
            
            <path 
                :d="pathData.fill" 
                :fill="`url(#gradient-${color?.replace('#', '')})`"
                class="transition-all duration-300 ease-in-out"
            />
            
            <path 
                :d="pathData.line" 
                :stroke="color || 'currentColor'" 
                :stroke-width="stroke" 
                vector-effect="non-scaling-stroke"
                stroke-linecap="round" 
                stroke-linejoin="round"
                class="transition-all duration-300 ease-in-out"
                fill="none"
            />
        </svg>

        <!-- Hover Indicator Dot -->
        <div 
            v-if="hoverIndex !== null"
            class="absolute rounded-full pointer-events-none z-40 transform -translate-x-1/2 -translate-y-1/2 transition-all duration-75"
            :style="dotStyle"
        ></div>

        <!-- Tooltip -->
        <div 
            v-if="hoverIndex !== null"
            class="absolute pointer-events-none z-50 transition-all duration-75"
            :style="tooltipStyle"
        >
            <div class="bg-card border shadow-lg text-foreground text-[10px] px-1.5 py-0.5 rounded font-mono whitespace-nowrap">
                {{ formatter ? formatter(data[hoverIndex]) : Math.round(data[hoverIndex]) }}
            </div>
        </div>
    </div>
</template>
