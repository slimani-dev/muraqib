import { useDocumentVisibility, useIntervalFn } from '@vueuse/core';
import type { MaybeRefOrGetter, Ref } from 'vue';
import { onMounted, ref, toValue, watch } from 'vue';

export type ServerStatusState = 'up' | 'blocked' | 'down';

/**
 * How a service's accessibility is checked (see App\Services\Media\MediaStatusChecker::forDashboard):
 * `browser` checks run here; `server` checks run in Laravel and arrive with the polled props.
 */
export type StatusCheckTarget =
    | { mode: 'browser'; url: string }
    | { mode: 'server'; state: ServerStatusState };

export type StatusState = ServerStatusState | 'checking' | 'unknown';

const BROWSER_CHECK_TIMEOUT_MS = 8000;

/**
 * Is the service accessible from where the dashboard is opened? A browser check is a
 * `no-cors` request: the response can't be read, but any answer means up and a network
 * error or timeout means down. Pauses while the tab is hidden.
 */
export function useStatusCheck(
    target: MaybeRefOrGetter<StatusCheckTarget | null | undefined>,
    intervalMs = 60_000,
): { state: Ref<StatusState>; check: () => Promise<void> } {
    const state = ref<StatusState>('checking');

    const check = async (): Promise<void> => {
        const current = toValue(target);

        if (!current) {
            state.value = 'unknown';

            return;
        }

        if (current.mode === 'server') {
            state.value = current.state;

            return;
        }

        try {
            await fetch(current.url, {
                mode: 'no-cors',
                cache: 'no-store',
                signal: AbortSignal.timeout(BROWSER_CHECK_TIMEOUT_MS),
            });
            state.value = 'up';
        } catch {
            state.value = 'down';
        }
    };

    // Started on mount, so a server-side render never pings anything
    const { pause, resume } = useIntervalFn(check, intervalMs, {
        immediate: false,
    });

    onMounted(() => {
        void check();
        resume();
    });
    const visibility = useDocumentVisibility();

    watch(visibility, (value) => {
        if (value === 'visible') {
            resume();
        } else {
            pause();
        }
    });

    watch(() => toValue(target), check, { deep: true });

    return { state, check };
}
