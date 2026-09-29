import type { WidgetDefinition } from './widgets/registry';
import type { WidgetMode } from './widgets/widgetMode';

/**
 * Column layout of the dashboard (left zone, middle zone, right zone).
 * Shared with the widget preview page so previews match the real widths.
 */
export const DASHBOARD_GRID_CLASS =
    'grid grid-cols-1 items-start gap-4 md:grid-cols-[20%_1fr_20%] lg:grid-cols-[22%_1fr_22%] xl:grid-cols-[20%_1fr_20%]';

export type WidgetColumns = 1 | 2 | 3;
export type WidgetRows = 1 | 2 | 3 | 4;

/**
 * A widget placed in the middle zone, which is a 3-column grid on wide screens.
 * `widget` is a widget id, or `media:<type>` for every enabled media service of that type.
 */
export type MiddleZoneItem = {
    widget: string;
    columns: WidgetColumns;
    rows?: WidgetRows;
};

/**
 * The middle zone is a container; below 48rem it collapses to one column.
 * `grid-flow-row-dense` lets 1-column widgets fill the gaps beside taller ones.
 */
export const MIDDLE_ZONE_CONTAINER_CLASS = '@container/zone';
export const MIDDLE_ZONE_GRID_CLASS =
    'grid grid-flow-row-dense grid-cols-1 gap-4 @3xl/zone:grid-cols-3';

const columnSpanClasses: Record<WidgetColumns, string> = {
    1: '@3xl/zone:col-span-1',
    2: '@3xl/zone:col-span-2',
    3: '@3xl/zone:col-span-3',
};

const rowSpanClasses: Record<WidgetRows, string> = {
    1: '',
    2: '@3xl/zone:row-span-2',
    3: '@3xl/zone:row-span-3',
    4: '@3xl/zone:row-span-4',
};

export const middleZoneItemClass = (
    columns: WidgetColumns,
    rows: WidgetRows = 1,
): string => `${columnSpanClasses[columns]} ${rowSpanClasses[rows]}`.trim();

const modesByColumns: Record<WidgetColumns, WidgetMode> = {
    3: 'desktop',
    2: 'medium',
    1: 'mobile',
};

/** Each column span has its own variation; one column is the mobile version. */
export const modeForColumns = (columns: WidgetColumns): WidgetMode =>
    modesByColumns[columns];

/** Default middle zone, matching the layout the dashboard had before widgets were split. */
export const defaultMiddleZone: MiddleZoneItem[] = [
    { widget: 'portainer', columns: 3 },
    { widget: 'media:jellyfin', columns: 2, rows: 2 },
    { widget: 'media:radarr', columns: 1 },
    { widget: 'media:sonarr', columns: 1 },
    { widget: 'media:seerr', columns: 2, rows: 2 },
    { widget: 'media:bazarr', columns: 1 },
    { widget: 'media:transmission', columns: 1 },
    { widget: 'repos', columns: 3 },
    { widget: 'contributions', columns: 3 },
    { widget: 'github-inbox', columns: 2, rows: 2 },
    { widget: 'repo-trends', columns: 1 },
    { widget: 'releases', columns: 2 },
    { widget: 'issues', columns: 2 },
    { widget: 'ci-status', columns: 1 },
];

export type PlacedWidget = MiddleZoneItem & { definition: WidgetDefinition };

/**
 * Resolve a middle zone layout to the widgets that exist: `media:<type>` slots
 * become one item per enabled service of that type, unknown widgets are dropped.
 */
export const resolveMiddleZone = (
    zone: MiddleZoneItem[],
    available: WidgetDefinition[],
): PlacedWidget[] =>
    zone.flatMap((item) => {
        if (item.widget.startsWith('media:')) {
            const type = item.widget.slice('media:'.length);

            return available
                .filter((definition) => definition.mediaType === type)
                .map((definition) => ({
                    ...item,
                    widget: definition.id,
                    definition,
                }));
        }

        const definition = available.find(
            (candidate) => candidate.id === item.widget,
        );

        return definition ? [{ ...item, definition }] : [];
    });

/** Where a widget sits in the default middle zone, if it does. */
export const defaultMiddleZoneItem = (
    definition: WidgetDefinition | undefined,
): MiddleZoneItem | undefined =>
    definition
        ? defaultMiddleZone.find(
              (item) =>
                  item.widget === definition.id ||
                  (definition.mediaType &&
                      item.widget === `media:${definition.mediaType}`),
          )
        : undefined;

/*
 * Page layouts (stored per dashboard page, validated by App\Rules\DashboardLayout).
 *
 * Nesting is deliberately shallow:
 * - a zone holds widgets, sections and tabbed sections;
 * - a tabbed section's tabs hold widgets and sections;
 * - a section holds widgets only.
 */

export type ZoneName = 'left' | 'middle' | 'right';

export const zoneNames: ZoneName[] = ['left', 'middle', 'right'];

export type WidgetItem = {
    id: string;
    kind: 'widget';
    widget: string;
    columns?: WidgetColumns;
    rows?: WidgetRows;
};
export type SectionItem = {
    id: string;
    kind: 'section';
    title: string;
    columns?: WidgetColumns;
    rows?: WidgetRows;
    items: WidgetItem[];
};
export type Tab = {
    id: string;
    title: string;
    items: (WidgetItem | SectionItem)[];
};
export type TabsItem = {
    id: string;
    kind: 'tabs';
    title?: string;
    columns?: WidgetColumns;
    rows?: WidgetRows;
    default_tab: string;
    tabs: Tab[];
};
export type LayoutItem = WidgetItem | SectionItem | TabsItem;
export type DashboardLayout = {
    version: 1;
    zones: Record<ZoneName, LayoutItem[]>;
};

/** Which item kinds each kind of list accepts. */
export const acceptedKinds = {
    zone: ['widget', 'section', 'tabs'],
    tab: ['widget', 'section'],
    section: ['widget'],
} as const satisfies Record<string, LayoutItem['kind'][]>;

export type ListKind = keyof typeof acceptedKinds;

export const newId = (): string => Math.random().toString(36).slice(2, 10);

export const newWidgetItem = (
    widget: string,
    columns: WidgetColumns = 1,
): WidgetItem => ({ id: newId(), kind: 'widget', widget, columns });

export const newSection = (): SectionItem => ({
    id: newId(),
    kind: 'section',
    title: 'New section',
    columns: 3,
    items: [],
});

export const newTabs = (): TabsItem => {
    const overview = { id: newId(), title: 'Overview', items: [] };

    return {
        id: newId(),
        kind: 'tabs',
        columns: 3,
        default_tab: overview.id,
        tabs: [overview, { id: newId(), title: 'Details', items: [] }],
    };
};

export const emptyLayout = (): DashboardLayout => ({
    version: 1,
    zones: { left: [], middle: [], right: [] },
});

/**
 * The built-in layout of a team's default page, used until the page is customised:
 * today's arrangement, with one widget per media service and Netdata server.
 */
export const defaultLayout = (
    available: WidgetDefinition[],
): DashboardLayout => {
    const has = (id: string) =>
        available.some((definition) => definition.id === id);
    const widget = (id: string): WidgetItem[] =>
        has(id) ? [newWidgetItem(id)] : [];

    return {
        version: 1,
        zones: {
            // Inbox and Pi-hole are demo data for now, so they're only in the widget catalog
            left: [
                ...widget('calendar'),
                ...widget('network'),
                ...widget('my-repos'),
                ...widget('open-source'),
            ],
            middle: resolveMiddleZone(defaultMiddleZone, available).map(
                (placed) => ({
                    ...newWidgetItem(placed.definition.id, placed.columns),
                    ...(placed.rows ? { rows: placed.rows } : {}),
                }),
            ),
            right: [
                ...widget('weather'),
                ...netdataSection(available),
                ...widget('pull-requests'),
                ...widget('container-status'),
            ],
        },
    };
};

const netdataSection = (available: WidgetDefinition[]): SectionItem[] => {
    const servers = available.filter((definition) => definition.netdataServer);

    return servers.length
        ? [
              {
                  id: newId(),
                  kind: 'section',
                  title: 'Infrastructure · Netdata',
                  items: servers.map((definition) =>
                      newWidgetItem(definition.id),
                  ),
              },
          ]
        : [];
};

/** Every widget id placed anywhere in the layout. */
export const placedWidgets = (layout: DashboardLayout): Set<string> => {
    const ids = new Set<string>();
    const visit = (items: LayoutItem[]) =>
        items.forEach((item) => {
            if (item.kind === 'widget') {
                ids.add(item.widget);
            } else if (item.kind === 'section') {
                visit(item.items);
            } else {
                item.tabs.forEach((tab) => visit(tab.items));
            }
        });

    zoneNames.forEach((zone) => visit(layout.zones[zone]));

    return ids;
};

/**
 * Drop widgets that no longer exist (a removed media service or Netdata server),
 * so an old layout keeps working.
 */
export const pruneLayout = (
    layout: DashboardLayout,
    available: WidgetDefinition[],
): DashboardLayout => {
    const ids = new Set(available.map((definition) => definition.id));
    const keepWidgets = <T extends LayoutItem>(items: T[]): T[] =>
        items
            .filter((item) => item.kind !== 'widget' || ids.has(item.widget))
            .map((item) => {
                if (item.kind === 'section') {
                    return { ...item, items: keepWidgets(item.items) };
                }

                if (item.kind === 'tabs') {
                    return {
                        ...item,
                        tabs: item.tabs.map((tab) => ({
                            ...tab,
                            items: keepWidgets(tab.items),
                        })),
                    };
                }

                return item;
            });

    return {
        version: 1,
        zones: {
            left: keepWidgets(layout.zones.left),
            middle: keepWidgets(layout.zones.middle),
            right: keepWidgets(layout.zones.right),
        },
    };
};

/** Grid of a section or tab inside the middle zone: as many columns as the container spans. */
export const nestedGridClass = (columns: WidgetColumns): string =>
    ({
        1: 'grid grid-cols-1 gap-4',
        2: 'grid grid-flow-row-dense grid-cols-1 gap-4 @3xl/zone:grid-cols-2',
        3: 'grid grid-flow-row-dense grid-cols-1 gap-4 @3xl/zone:grid-cols-3',
    })[columns];
