<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Github, LayoutGrid, Menu, PanelsTopLeft, Pencil, Plus, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AddPageDialog from '@/components/dashboard/editor/AddPageDialog.vue';
import AppearanceDropdown from '@/components/kit/AppearanceDropdown.vue';
import AppLogo from '@/components/kit/AppLogo.vue';
import AppLogoIcon from '@/components/kit/AppLogoIcon.vue';
import Breadcrumbs from '@/components/kit/Breadcrumbs.vue';
import TeamSwitcher from '@/components/kit/TeamSwitcher.vue';
import UserMenuContent from '@/components/kit/UserMenuContent.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    NavigationMenu,
    NavigationMenuItem,
    NavigationMenuList,
    navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { toUrl } from '@/lib/utils';
import { dashboard } from '@/routes';
import { page as dashboardPageRoute } from '@/routes/dashboard';
import { useDashboardEditStore } from '@/stores/useDashboardEditStore';
import type { BreadcrumbItem, NavItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

const props = withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const auth = computed(() => page.props.auth);
const { isCurrentUrl, whenCurrentUrl } = useCurrentUrl();

const dashboardUrl = computed(() =>
    page.props.currentTeam ? dashboard(page.props.currentTeam.slug).url : '/',
);

const activeItemStyles =
    'text-neutral-900 dark:text-neutral-100';

/** One tab per dashboard page; the default page lives at /{team}/dashboard. */
const mainNavItems = computed<NavItem[]>(() => {
    const team = page.props.currentTeam?.slug;
    const pages = page.props.dashboardPages ?? [];

    if (!team || !pages.length) {
        return [{ title: 'Dashboard', href: dashboardUrl.value, icon: LayoutGrid }];
    }

    return pages.map((dashboardPage) => ({
        title: dashboardPage.name,
        href: dashboardPage.is_default ? dashboardUrl.value : dashboardPageRoute({ current_team: team, page: dashboardPage.slug }).url,
        icon: dashboardPage.is_default ? LayoutGrid : PanelsTopLeft,
    }));
});

/** Edit mode is available on dashboard pages to team owners and admins. */
const editStore = useDashboardEditStore();
const canEdit = computed(() => page.component === 'Dashboard' && page.props.canEditDashboard);
const addPageOpen = ref(false);

const rightNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/slimani-dev/muraqib',
        icon: Github,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <!-- On a phone the dashboard moves this header into its bottom bar (MobileDashboardBar) -->
    <div :class="{ 'hidden md:block': page.component === 'Dashboard' }">
        <div class="border-b border-sidebar-border/80">
            <div class="mx-auto flex h-16 items-center px-4">
                <!-- Mobile Menu -->
                <div class="lg:hidden">
                    <Sheet>
                        <SheetTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="mr-2 h-9 w-9"
                            >
                                <Menu class="h-5 w-5" />
                            </Button>
                        </SheetTrigger>
                        <SheetContent side="left" class="w-[300px] p-6">
                            <SheetTitle class="sr-only"
                                >Navigation menu</SheetTitle
                            >
                            <SheetHeader class="flex justify-start text-left">
                                <AppLogoIcon
                                    class="size-6 fill-current text-black dark:text-white"
                                />
                            </SheetHeader>
                            <div
                                class="flex h-full flex-1 flex-col justify-between space-y-4 py-6"
                            >
                                <nav class="-mx-3 space-y-1">
                                    <Link
                                        v-for="item in mainNavItems"
                                        :key="item.title"
                                        :href="item.href"
                                        class="flex items-center gap-x-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-accent"
                                        :class="
                                            whenCurrentUrl(
                                                item.href,
                                                activeItemStyles,
                                            )
                                        "
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="h-5 w-5"
                                        />
                                        {{ item.title }}
                                    </Link>
                                </nav>
                                <div class="flex flex-col space-y-4">
                                    <a
                                        v-for="item in rightNavItems"
                                        :key="item.title"
                                        :href="toUrl(item.href)"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center space-x-2 text-sm font-medium"
                                    >
                                        <component
                                            v-if="item.icon"
                                            :is="item.icon"
                                            class="h-5 w-5"
                                        />
                                        <span>{{ item.title }}</span>
                                    </a>
                                </div>
                            </div>
                        </SheetContent>
                    </Sheet>
                </div>

                <Link :href="dashboardUrl" class="flex items-center gap-x-2">
                    <AppLogo />
                </Link>

                <div class="ml-4 flex items-center">
                    <TeamSwitcher :in-header="true" />
                </div>

                <!-- Desktop Menu -->
                <div class="hidden h-full lg:flex lg:flex-1">
                    <NavigationMenu class="ml-10 flex h-full items-stretch">
                        <NavigationMenuList
                            class="flex h-full items-stretch space-x-2"
                        >
                            <NavigationMenuItem
                                v-for="(item, index) in mainNavItems"
                                :key="index"
                                class="relative flex h-full items-center"
                            >
                                <Link
                                    :class="[
                                        navigationMenuTriggerStyle(),
                                        whenCurrentUrl(
                                            item.href,
                                            activeItemStyles,
                                        ),
                                        'h-9 cursor-pointer px-3',
                                    ]"
                                    :href="item.href"
                                >
                                    <component
                                        v-if="item.icon"
                                        :is="item.icon"
                                        class="mr-2 h-4 w-4"
                                    />
                                    {{ item.title }}
                                </Link>
                                <div
                                    v-if="isCurrentUrl(item.href)"
                                    class="absolute bottom-0 left-0 h-0.5 w-full translate-y-px bg-black dark:bg-white"
                                ></div>
                            </NavigationMenuItem>
                            <NavigationMenuItem v-if="canEdit" class="flex h-full items-center gap-1">
                                <Button v-if="editStore.editing" variant="ghost" size="sm" class="h-9 cursor-pointer px-2" title="Add a page" @click="addPageOpen = true">
                                    <Plus class="h-4 w-4" />
                                </Button>
                                <Button :variant="editStore.editing ? 'default' : 'ghost'" size="sm" class="h-9 cursor-pointer px-2.5"
                                    :title="editStore.editing ? 'Editing the dashboard' : 'Edit the dashboard'" @click="editStore.start()">
                                    <Pencil class="h-4 w-4" />
                                    <span v-if="editStore.editing" class="ml-1">Editing</span>
                                </Button>
                            </NavigationMenuItem>
                        </NavigationMenuList>
                    </NavigationMenu>
                    <AddPageDialog v-model:open="addPageOpen" />
                </div>

                <div class="ml-auto flex items-center space-x-2">
                    <div class="relative flex items-center space-x-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            class="group h-9 w-9 cursor-pointer"
                        >
                            <Search
                                class="size-5 opacity-80 group-hover:opacity-100"
                            />
                        </Button>

                        <div class="hidden space-x-1 lg:flex">
                            <template
                                v-for="item in rightNavItems"
                                :key="item.title"
                            >
                                <TooltipProvider :delay-duration="0">
                                    <Tooltip>
                                        <TooltipTrigger>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                as-child
                                                class="group h-9 w-9 cursor-pointer"
                                            >
                                                <a
                                                    :href="toUrl(item.href)"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    <span class="sr-only">{{
                                                        item.title
                                                    }}</span>
                                                    <component
                                                        :is="item.icon"
                                                        class="size-5 opacity-80 group-hover:opacity-100"
                                                    />
                                                </a>
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{{ item.title }}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                </TooltipProvider>
                            </template>
                        </div>
                    </div>

                    <AppearanceDropdown />

                    <DropdownMenu>
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar
                                    class="size-8 overflow-hidden rounded-full"
                                >
                                    <AvatarImage
                                        v-if="auth.user.avatar"
                                        :src="auth.user.avatar"
                                        :alt="auth.user.name"
                                    />
                                    <AvatarFallback
                                        class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="auth.user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <div
            v-if="props.breadcrumbs.length > 1"
            class="flex w-full border-b border-sidebar-border/70"
        >
            <div
                class="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl"
            >
                <Breadcrumbs :breadcrumbs="breadcrumbs || []" />
            </div>
        </div>
    </div>
</template>
