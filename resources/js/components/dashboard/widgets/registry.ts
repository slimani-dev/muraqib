import type { Component } from 'vue';
import CalendarCard from '../CalendarCard.vue';
import CiStatusCard from '../CiStatusCard.vue';
import ContainerStatusCard from '../ContainerStatusCard.vue';
import ContributionsCard from '../ContributionsCard.vue';
import DockerContainersCard from '../DockerContainersCard.vue';
import GitHubInboxCard from '../GitHubInboxCard.vue';
import InboxCard from '../InboxCard.vue';
import IssuesCard from '../IssuesCard.vue';
import type { MediaService } from '../media/MediaServiceWidget.vue';
import MediaServiceWidget from '../media/MediaServiceWidget.vue';
import type { ConfiguredMediaService } from '../media/mediaWidget';
import MyReposStatsCard from '../MyReposStatsCard.vue';
import NetdataServerCard from '../NetdataServerCard.vue';
import NetworkSpeedCard from '../NetworkSpeedCard.vue';
import OpenSourceStatsCard from '../OpenSourceStatsCard.vue';
import PiHoleCard from '../PiHoleCard.vue';
import PullRequestsCard from '../PullRequestsCard.vue';
import ReleasesCard from '../ReleasesCard.vue';
import ReposCard from '../ReposCard.vue';
import RepoTrendsCard from '../RepoTrendsCard.vue';
import WeatherCard from '../WeatherCard.vue';

export type WidgetCategory = 'infrastructure' | 'media' | 'code' | 'other';

export const widgetCategories: { value: WidgetCategory; label: string }[] = [
    { value: 'infrastructure', label: 'Infrastructure' },
    { value: 'media', label: 'Media' },
    { value: 'code', label: 'Code' },
    { value: 'other', label: 'Other' },
];

export type WidgetDefinition = {
    /** Stable id stored in page layouts. Must match App\Services\Dashboard\WidgetCatalog. */
    id: string;
    title: string;
    /** Lucide icon name, for the widget catalog. */
    icon: string;
    category: WidgetCategory;
    component: Component;
    /** Builds the widget's props from the page's dashboard data. */
    props: (data: Record<string, any>) => Record<string, unknown>;
    /** Set on media service widgets: the service type, used to place them by type. */
    mediaType?: MediaService;
    /** Set on Netdata widgets: one widget per server. */
    netdataServer?: boolean;
};

const noProps = () => ({});

const mediaTypeLabels: Record<MediaService, string> = {
    jellyfin: 'Jellyfin',
    seerr: 'Seerr',
    radarr: 'Radarr',
    sonarr: 'Sonarr',
    bazarr: 'Bazarr',
    transmission: 'Transmission',
};

/**
 * One widget per enabled media service. A type can have several services
 * (two Jellyfins, two torrent clients), so these come from the `media_services` prop.
 */
export const mediaWidgets = (data: Record<string, any>): WidgetDefinition[] =>
    ((data.media_services ?? []) as ConfiguredMediaService[]).map(
        (service) => ({
            id: `media-${service.id}`,
            title:
                service.name === mediaTypeLabels[service.type]
                    ? service.name
                    : `${service.name} (${mediaTypeLabels[service.type]})`,
            icon: 'clapperboard',
            category: 'media',
            component: MediaServiceWidget,
            mediaType: service.type,
            props: (current) => ({
                service: service.type,
                serviceId: service.id,
                initialData: current[`media_${service.id}`],
                cachedData: current[`media_${service.id}_cached`],
            }),
        }),
    );

/** One widget per active Netdata server, fed by the dashboard's live Netdata polling. */
export const netdataWidgets = (data: Record<string, any>): WidgetDefinition[] =>
    ((data.netdata ?? []) as { id: number; name: string }[]).map((server) => ({
        id: `netdata-${server.id}`,
        title: `Netdata: ${server.name}`,
        icon: 'server',
        category: 'infrastructure',
        component: NetdataServerCard,
        netdataServer: true,
        props: (current) => {
            const live =
                (current.netdata ?? []).find(
                    (candidate: { id: number }) => candidate.id === server.id,
                ) ?? server;

            return {
                server: live,
                loading: !live.stats,
                timeframe: current.netdataTimeframes?.[server.id] ?? '1h',
            };
        },
    }));

/** Widgets that exist once per dashboard page. */
export const widgets: WidgetDefinition[] = [
    {
        id: 'portainer',
        title: 'Docker containers (Portainer)',
        icon: 'container',
        category: 'infrastructure',
        component: DockerContainersCard,
        props: (data) => ({ containers: data.containers }),
    },
    {
        id: 'container-status',
        title: 'Container status',
        icon: 'box',
        category: 'infrastructure',
        component: ContainerStatusCard,
        props: (data) => ({ containers: data.containers }),
    },
    {
        id: 'network',
        title: 'Network speed',
        icon: 'activity',
        category: 'infrastructure',
        component: NetworkSpeedCard,
        props: (data) => ({
            server: data.netdata ? (data.netdata[0] ?? null) : undefined,
            latency: data.network_latency,
        }),
    },
    {
        id: 'pihole',
        title: 'Pi-hole (demo data)',
        icon: 'shield',
        category: 'infrastructure',
        component: PiHoleCard,
        props: noProps,
    },
    {
        id: 'calendar',
        title: 'Calendar',
        icon: 'calendar',
        category: 'media',
        component: CalendarCard,
        props: (data) => ({ events: data.agenda ?? data.agenda_cached ?? [] }),
    },
    {
        id: 'repos',
        title: 'Managed repositories',
        icon: 'package',
        category: 'code',
        component: ReposCard,
        props: (data) => ({ repositories: data.repositories }),
    },
    {
        id: 'my-repos',
        title: 'My repos (totals)',
        icon: 'package',
        category: 'code',
        component: MyReposStatsCard,
        props: (data) => ({ repositories: data.repositories }),
    },
    {
        id: 'open-source',
        title: 'Open source',
        icon: 'git-branch',
        category: 'code',
        component: OpenSourceStatsCard,
        props: (data) => ({ repositories: data.repositories }),
    },
    {
        id: 'pull-requests',
        title: 'Awaiting review',
        icon: 'git-pull-request',
        category: 'code',
        component: PullRequestsCard,
        props: (data) => ({ repositories: data.repositories }),
    },
    {
        id: 'repo-trends',
        title: 'Stars & downloads',
        icon: 'trending-up',
        category: 'code',
        component: RepoTrendsCard,
        props: (data) => ({
            repositories: data.repositories,
            trends: data.repository_trends,
        }),
    },
    {
        id: 'contributions',
        title: 'Contributions',
        icon: 'calendar-days',
        category: 'code',
        component: ContributionsCard,
        props: (data) => ({ accounts: data.contributions }),
    },
    {
        id: 'github-inbox',
        title: 'GitHub inbox',
        icon: 'bell',
        category: 'code',
        component: GitHubInboxCard,
        props: (data) => ({ accounts: data.github_inbox }),
    },
    {
        id: 'releases',
        title: 'Latest releases',
        icon: 'tag',
        category: 'code',
        component: ReleasesCard,
        props: (data) => ({ repositories: data.repositories }),
    },
    {
        id: 'ci-status',
        title: 'CI status',
        icon: 'workflow',
        category: 'code',
        component: CiStatusCard,
        props: (data) => ({ repositories: data.repositories }),
    },
    {
        id: 'issues',
        title: 'Recent issues',
        icon: 'circle-dot',
        category: 'code',
        component: IssuesCard,
        props: (data) => ({ repositories: data.repositories }),
    },
    {
        id: 'weather',
        title: 'Weather',
        icon: 'cloud-sun',
        category: 'other',
        component: WeatherCard,
        props: (data) => ({
            weather: data.weather,
            weather_cached: data.weather_cached,
        }),
    },
    {
        id: 'inbox',
        title: 'Inboxes (demo data)',
        icon: 'mail',
        category: 'other',
        component: InboxCard,
        props: noProps,
    },
];

/** Every widget available with this dashboard data: the fixed ones plus one per media service and Netdata server. */
export const widgetsFor = (data: Record<string, any>): WidgetDefinition[] => [
    ...widgets,
    ...netdataWidgets(data),
    ...mediaWidgets(data),
];

export const findWidget = (
    id: string,
    data: Record<string, any>,
): WidgetDefinition | undefined =>
    widgetsFor(data).find((widget) => widget.id === id);
