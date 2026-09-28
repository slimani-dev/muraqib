import type { ComputedRef, InjectionKey, Ref } from 'vue';
import { inject } from 'vue';
import type { DashboardLayout, LayoutItem, ListKind, TabsItem } from '../layout';
import { acceptedKinds, newId, zoneNames } from '../layout';
import type { WidgetDefinition } from '../widgets/registry';

/**
 * Shared by every part of the dashboard layout (zones, sections, tabs, widgets).
 * Nested components change the draft layout through these functions rather than
 * mutating their props.
 */
export type LayoutEditor = {
    editing: Readonly<Ref<boolean>>;
    /** Every widget that exists with the current data. */
    available: ComputedRef<WidgetDefinition[]>;
    /** Page props (plus live Netdata) that widgets build their props from. */
    data: ComputedRef<Record<string, any>>;
    replaceList: (owner: Record<string, any>, key: string, items: LayoutItem[]) => void;
    update: (target: object, patch: Record<string, unknown>) => void;
    remove: (item: LayoutItem) => void;
    addTab: (tabs: TabsItem) => void;
    removeTab: (tabs: TabsItem, tabId: string) => void;
    /** Widgets shown as just their toolbar while editing (not saved). */
    isCollapsed: (id: string) => boolean;
    toggleCollapsed: (id: string) => void;
};

export const layoutEditorKey: InjectionKey<LayoutEditor> = Symbol('layoutEditor');

export const useLayoutEditor = (): LayoutEditor => {
    const editor = inject(layoutEditorKey);

    if (!editor) {
        throw new Error('useLayoutEditor() needs a dashboard layout above it.');
    }

    return editor;
};

/** The editing operations, working on the draft layout. */
export const layoutOperations = (draft: Ref<DashboardLayout>) => {
    const removeFrom = (items: LayoutItem[], target: LayoutItem): boolean => {
        const index = items.indexOf(target);

        if (index !== -1) {
            items.splice(index, 1);

            return true;
        }

        return items.some((item) => (item.kind === 'section' && removeFrom(item.items, target))
            || (item.kind === 'tabs' && item.tabs.some((tab) => removeFrom(tab.items, target))));
    };

    return {
        replaceList: (owner: Record<string, any>, key: string, items: LayoutItem[]) => {
            owner[key] = items;
        },
        update: (target: object, patch: Record<string, unknown>) => {
            Object.assign(target, patch);
        },
        remove: (item: LayoutItem) => {
            zoneNames.some((zone) => removeFrom(draft.value.zones[zone], item));
        },
        addTab: (tabs: TabsItem) => {
            tabs.tabs.push({ id: newId(), title: `Tab ${tabs.tabs.length + 1}`, items: [] });
        },
        removeTab: (tabs: TabsItem, tabId: string) => {
            if (tabs.tabs.length <= 1) {
                return;
            }

            tabs.tabs = tabs.tabs.filter((tab) => tab.id !== tabId);

            if (tabs.default_tab === tabId) {
                tabs.default_tab = tabs.tabs[0].id;
            }
        },
    };
};

/**
 * SortableJS `put` check: whether a list accepts the dragged element. Every item and
 * catalog entry carries `data-kind`, so sections can't go into sections and tabs stay top-level.
 */
export const acceptsDrop = (list: ListKind) => (_to: unknown, _from: unknown, dragged: HTMLElement): boolean =>
    (acceptedKinds[list] as readonly string[]).includes(dragged.dataset.kind ?? '');
