import type { InjectionKey, Ref } from 'vue';
import type {
    StatusCheckTarget,
    StatusState,
} from '@/composables/useStatusCheck';

/** One entry of the dashboard's `media_services` prop: an enabled media service. */
export type ConfiguredMediaService = {
    id: number;
    type:
        'jellyfin' | 'seerr' | 'radarr' | 'sonarr' | 'bazarr' | 'transmission';
    name: string;
    url: string;
    status: StatusCheckTarget | null;
};

/** What MediaServiceWidget shares with the block it renders (status pill, refresh button). */
export type MediaWidgetContext = {
    status: Ref<StatusState>;
    refreshing: Ref<boolean>;
    refresh: () => void;
};

export const mediaWidgetKey: InjectionKey<MediaWidgetContext> =
    Symbol('mediaWidget');
