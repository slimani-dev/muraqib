<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLogoIcon from '@/components/kit/AppLogoIcon.vue';
import TeamSwitcher from '@/components/kit/TeamSwitcher.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import type { Appearance } from '@/composables/useAppearance';
import { useAppearance } from '@/composables/useAppearance';
import { getInitials } from '@/composables/useInitials';
import { dashboard, logout } from '@/routes';
import { page as dashboardPageRoute } from '@/routes/dashboard';
import { edit as profileEdit } from '@/routes/profile';
import { useDashboardEditStore } from '@/stores/useDashboardEditStore';

const props = defineProps<{
    /** Panel labels, in order. */
    panels: string[];
    /** Fractional panel index from the scroll position, so the pill follows the finger. */
    progress: number;
    active: number;
    showBackToTop: boolean;
}>();

const emit = defineEmits<{
    select: [index: number];
    top: [];
}>();

const page = usePage();
const editStore = useDashboardEditStore();
const menuOpen = ref(false);

/** Width of one dot slot in px; the pill moves by this much per panel. */
const SLOT = 32;
const PILL = 20;

const pillStyle = computed(() => ({
    transform: `translateX(${props.progress * SLOT + (SLOT - PILL) / 2}px)`,
    width: `${PILL}px`,
}));

const pages = computed(() => {
    const team = page.props.currentTeam?.slug;

    return team
        ? (page.props.dashboardPages ?? []).map((dashboardPage) => ({
              ...dashboardPage,
              href: dashboardPage.is_default
                  ? dashboard(team).url
                  : dashboardPageRoute({
                        current_team: team,
                        page: dashboardPage.slug,
                    }).url,
          }))
        : [];
});

const user = computed(() => page.props.auth?.user);
const currentPath = computed(() => page.url.split('?')[0]);

const { appearance, updateAppearance } = useAppearance();
const appearanceOptions: { value: Appearance; label: string; icon: string }[] =
    [
        { value: 'light', label: 'Light', icon: 'lucide:sun' },
        { value: 'dark', label: 'Dark', icon: 'lucide:moon' },
        { value: 'system', label: 'System', icon: 'lucide:monitor' },
    ];

const startEditing = (): void => {
    menuOpen.value = false;
    editStore.start();
};
</script>

<template>
    <nav
        class="fixed inset-x-0 bottom-0 z-40 border-t bg-card/95 backdrop-blur-xl"
        style="padding-bottom: env(safe-area-inset-bottom)"
    >
        <div class="flex h-14 items-center justify-between px-3">
            <!-- ↑ back to top of the current panel -->
            <button
                type="button"
                class="flex h-10 w-10 items-center justify-center rounded-full transition-opacity"
                :class="
                    showBackToTop
                        ? 'cursor-pointer opacity-100 hover:bg-muted'
                        : 'pointer-events-none opacity-0'
                "
                aria-label="Back to top"
                @click="emit('top')"
            >
                <Icon icon="lucide:arrow-up" class="h-5 w-5" />
            </button>

            <!-- Panel dots; the active one stretches into a pill that slides with the scroll -->
            <div class="flex flex-col items-center gap-1">
                <div
                    class="relative flex h-2 items-center"
                    :style="{ width: `${panels.length * SLOT}px` }"
                >
                    <button
                        v-for="(label, index) in panels"
                        :key="label"
                        type="button"
                        class="flex h-6 cursor-pointer items-center justify-center"
                        :style="{ width: `${SLOT}px` }"
                        :aria-label="`Show ${label}`"
                        :aria-current="index === active ? 'true' : undefined"
                        @click="emit('select', index)"
                    >
                        <span
                            class="h-2 w-2 rounded-full bg-muted-foreground/50"
                        ></span>
                    </button>
                    <span
                        class="pointer-events-none absolute left-0 h-2 rounded-full bg-primary"
                        :style="pillStyle"
                    ></span>
                </div>
                <span class="text-[10px] font-medium text-muted-foreground">{{
                    panels[active]
                }}</span>
            </div>

            <!-- ☰ menu -->
            <button
                type="button"
                class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full hover:bg-muted"
                aria-label="Menu"
                @click="menuOpen = true"
            >
                <Icon icon="lucide:menu" class="h-5 w-5" />
            </button>
        </div>

        <!-- The header's menu, moved down here on a phone; slides in from the ☰ side -->
        <Sheet v-model:open="menuOpen">
            <SheetContent
                side="right"
                class="w-[300px] gap-0 overflow-y-auto p-0 pb-[env(safe-area-inset-bottom)]"
            >
                <SheetHeader class="border-b p-4">
                    <SheetTitle class="flex items-center gap-2 text-base">
                        <AppLogoIcon
                            class="size-6 fill-current text-black dark:text-white"
                        />
                        {{ page.props.name }}
                    </SheetTitle>
                    <SheetDescription class="sr-only"
                        >Navigation, team, appearance and
                        account</SheetDescription
                    >
                </SheetHeader>

                <div class="flex flex-1 flex-col gap-4 p-3">
                    <TeamSwitcher />

                    <nav class="flex flex-col gap-0.5">
                        <p
                            class="px-3 pb-1 text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                        >
                            Dashboard
                        </p>
                        <Link
                            v-for="item in pages"
                            :key="item.id"
                            :href="item.href"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                            :class="{ 'bg-accent': item.href === currentPath }"
                            @click="menuOpen = false"
                        >
                            <Icon
                                :icon="
                                    item.is_default
                                        ? 'lucide:layout-grid'
                                        : 'lucide:panels-top-left'
                                "
                                class="h-4 w-4 text-muted-foreground"
                            />
                            {{ item.name }}
                        </Link>
                        <button
                            v-if="
                                page.props.canEditDashboard &&
                                !editStore.editing
                            "
                            type="button"
                            class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium hover:bg-accent"
                            @click="startEditing"
                        >
                            <Icon
                                icon="lucide:pencil"
                                class="h-4 w-4 text-muted-foreground"
                            />
                            Edit dashboard
                        </button>
                        <a
                            href="/admin"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                        >
                            <Icon
                                icon="lucide:shield"
                                class="h-4 w-4 text-muted-foreground"
                            />
                            Admin panel
                        </a>
                    </nav>

                    <div class="flex flex-col gap-1.5">
                        <p
                            class="px-3 text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                        >
                            Appearance
                        </p>
                        <div
                            class="mx-1 grid grid-cols-3 gap-1 rounded-lg bg-muted p-1"
                        >
                            <button
                                v-for="option in appearanceOptions"
                                :key="option.value"
                                type="button"
                                class="flex cursor-pointer items-center justify-center gap-1.5 rounded-md py-1.5 text-xs font-medium transition-colors"
                                :class="
                                    appearance === option.value
                                        ? 'bg-background text-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground'
                                "
                                @click="updateAppearance(option.value)"
                            >
                                <Icon :icon="option.icon" class="h-3.5 w-3.5" />
                                {{ option.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Account -->
                    <div class="mt-auto flex flex-col gap-0.5 border-t pt-3">
                        <div
                            v-if="user"
                            class="flex items-center gap-3 px-3 pb-2"
                        >
                            <Avatar
                                class="h-9 w-9 overflow-hidden rounded-full"
                            >
                                <AvatarImage
                                    v-if="user.avatar"
                                    :src="user.avatar"
                                    :alt="user.name"
                                />
                                <AvatarFallback
                                    class="rounded-full bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                    >{{
                                        getInitials(user.name)
                                    }}</AvatarFallback
                                >
                            </Avatar>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold">
                                    {{ user.name }}
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ user.email }}
                                </p>
                            </div>
                        </div>
                        <Link
                            :href="profileEdit()"
                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                            @click="menuOpen = false"
                        >
                            <Icon
                                icon="lucide:settings"
                                class="h-4 w-4 text-muted-foreground"
                            />
                            Settings
                        </Link>
                        <Link
                            :href="logout()"
                            as="button"
                            class="flex cursor-pointer items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium text-destructive hover:bg-accent"
                        >
                            <Icon icon="lucide:log-out" class="h-4 w-4" /> Log
                            out
                        </Link>
                    </div>
                </div>
            </SheetContent>
        </Sheet>
    </nav>
</template>
