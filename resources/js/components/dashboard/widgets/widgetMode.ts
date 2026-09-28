import type { ComputedRef, InjectionKey } from 'vue';
import { computed, inject } from 'vue';

/**
 * The widest variation a widget may use, matching its column span:
 * `desktop` = 3 columns, `medium` = 2 columns, `mobile` = 1 column / side zone.
 * A wider mode still falls back to a narrower variation when the widget
 * doesn't have the room (see the width thresholds below).
 */
export type WidgetMode = 'desktop' | 'medium' | 'mobile';

/** Keep in sync with the 28rem / 48rem in the widget variants (resources/css/app.css). */
export const WIDGET_MEDIUM_MIN_WIDTH = 448;
export const WIDGET_DESKTOP_MIN_WIDTH = 768;

const rank: Record<WidgetMode, number> = { mobile: 0, medium: 1, desktop: 2 };

/** The variation actually rendered for a mode at a given width (0 = not measured yet). */
export function resolveWidgetLayout(mode: WidgetMode, width: number): WidgetMode {
    const fits: WidgetMode = width === 0 || width >= WIDGET_DESKTOP_MIN_WIDTH
        ? 'desktop'
        : width >= WIDGET_MEDIUM_MIN_WIDTH ? 'medium' : 'mobile';

    return rank[fits] < rank[mode] ? fits : mode;
}

export const widgetLayoutKey: InjectionKey<ComputedRef<WidgetMode>> = Symbol('widgetLayout');

/**
 * The layout a widget is actually rendering right now, for template logic that
 * CSS alone can't express. Prefer the `widget-md:` / `widget-lg:` variants for styling.
 */
export function useWidgetLayout() {
    const layout = inject(
        widgetLayoutKey,
        computed<WidgetMode>(() => 'desktop'),
    );

    return {
        layout,
        isMobile: computed(() => layout.value === 'mobile'),
        isMedium: computed(() => layout.value === 'medium'),
        isDesktop: computed(() => layout.value === 'desktop'),
    };
}
