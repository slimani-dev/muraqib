import { useEventListener, usePreferredReducedMotion } from '@vueuse/core';
import type { Ref } from 'vue';
import { computed, nextTick, ref, watch } from 'vue';

const STORAGE_KEY = 'dashboard:panel';

const readStoredPanel = (): number | null => {
    try {
        const value = window.localStorage.getItem(STORAGE_KEY);

        return value === null ? null : Number(value);
    } catch {
        return null;
    }
};

const storePanel = (index: number): void => {
    try {
        window.localStorage.setItem(STORAGE_KEY, String(index));
    } catch {
        // Only a convenience: without storage the dashboard opens on the main panel
    }
};

/**
 * Horizontal, CSS scroll-snapped panels (the dashboard zones on a phone). Exposes the
 * scroll position as a fractional panel index, so an indicator can follow the finger,
 * and remembers the last panel. Uses native scrolling, so it keeps momentum and
 * rubber-banding, and stays out of the way of drag-and-drop.
 */
export function useSnapPanels(
    track: Ref<HTMLElement | null>,
    count: number,
    initial = 1,
    dragEnabled: Ref<boolean> = ref(true),
) {
    /** 0 = first panel, 1.5 = halfway between the second and the third. */
    const progress = ref(initial);
    const active = computed(() => Math.round(progress.value));
    const reducedMotion = usePreferredReducedMotion();

    const panelWidth = (): number => {
        const first = track.value?.firstElementChild as HTMLElement | null;

        return first?.offsetWidth ?? track.value?.clientWidth ?? 1;
    };

    const update = (): void => {
        if (!track.value) {
            return;
        }

        // Panels are narrower than the track, so measure against how far it can scroll
        const maxScroll = track.value.scrollWidth - track.value.clientWidth;
        progress.value =
            maxScroll > 0
                ? (track.value.scrollLeft / maxScroll) * (count - 1)
                : 0;
    };

    const scrollTo = (index: number, smooth = true): void => {
        const panel = track.value?.children[index] as HTMLElement | undefined;

        if (!track.value || !panel) {
            return;
        }

        // The track is `relative`, so offsetLeft is measured from it
        const left =
            panel.offsetLeft -
            (track.value.clientWidth - panel.offsetWidth) / 2;
        track.value.scrollTo({
            left,
            behavior:
                smooth && reducedMotion.value !== 'reduce' ? 'smooth' : 'auto',
        });
    };

    /*
     * Mouse drag to switch panels. Touch swipes already scroll natively; a mouse
     * can't drag a scroll container, so this adds it: drag sideways past a small
     * threshold, then release to snap to the nearest panel (a quick flick counts).
     */
    const DRAG_THRESHOLD = 8;
    let drag: {
        x: number;
        y: number;
        scrollLeft: number;
        time: number;
        moving: boolean;
    } | null = null;

    const endDrag = (event: PointerEvent): void => {
        if (!drag || !track.value) {
            drag = null;

            return;
        }

        const wasMoving = drag.moving;
        const distance = event.clientX - drag.x;
        const velocity = distance / Math.max(1, performance.now() - drag.time);
        drag = null;

        if (!wasMoving) {
            return;
        }

        track.value.style.scrollSnapType = '';
        track.value.style.cursor = '';
        track.value.style.userSelect = '';

        // Snap to the nearest panel, or the next one in the flick's direction
        const flick = Math.abs(velocity) > 0.4 ? -Math.sign(velocity) : 0;
        const target = Math.max(
            0,
            Math.min(
                count - 1,
                flick ? Math.round(progress.value + flick * 0.5) : active.value,
            ),
        );
        scrollTo(target);

        // The pointer-up turns into a click on whatever is under it: swallow that one
        window.addEventListener(
            'click',
            (click) => {
                click.stopPropagation();
                click.preventDefault();
            },
            { capture: true, once: true },
        );
    };

    useEventListener(track, 'pointerdown', (event: PointerEvent) => {
        if (
            event.pointerType !== 'mouse' ||
            event.button !== 0 ||
            !dragEnabled.value ||
            !track.value
        ) {
            return;
        }

        drag = {
            x: event.clientX,
            y: event.clientY,
            scrollLeft: track.value.scrollLeft,
            time: performance.now(),
            moving: false,
        };
    });

    useEventListener('pointermove', (event: PointerEvent) => {
        if (!drag || !track.value) {
            return;
        }

        const dx = event.clientX - drag.x;

        if (!drag.moving) {
            // Only a mostly-sideways drag switches panels; vertical drags and clicks are left alone
            if (
                Math.abs(dx) < DRAG_THRESHOLD ||
                Math.abs(dx) < Math.abs(event.clientY - drag.y)
            ) {
                return;
            }

            drag.moving = true;
            track.value.style.scrollSnapType = 'none';
            track.value.style.cursor = 'grabbing';
            track.value.style.userSelect = 'none';
            window.getSelection()?.removeAllRanges();
        }

        event.preventDefault();
        track.value.scrollLeft = drag.scrollLeft - dx;
    });

    // Starting on an image or link would begin the browser's own drag and cancel ours
    useEventListener(track, 'dragstart', (event: DragEvent) => {
        if (drag) {
            event.preventDefault();
        }
    });

    useEventListener('pointerup', endDrag);
    useEventListener('pointercancel', endDrag);

    useEventListener(track, 'scroll', update, { passive: true });
    useEventListener(track, 'scrollend', () => storePanel(active.value), {
        passive: true,
    });

    // Open on the remembered panel (or the main one) without animating
    watch(
        track,
        async (element) => {
            if (element) {
                await nextTick();
                const stored = readStoredPanel();
                scrollTo(
                    stored !== null && stored >= 0 && stored < count
                        ? stored
                        : initial,
                    false,
                );
                update();
            }
        },
        { immediate: true },
    );

    return { progress, active, scrollTo, panelWidth };
}
