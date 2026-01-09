<script setup lang="ts">
import AppearanceTabsCompact from '@/components/AppearanceTabsCompact.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useSidebar } from '@/components/ui/sidebar/utils';
import { logout } from '@/routes';
import { edit as profileEdit } from '@/routes/profile';
import { redirect as settingsRedirect } from '@/routes/settings';
import { Link, usePage } from '@inertiajs/vue3';
import { LogOut, Settings, User } from 'lucide-vue-next';

const page = usePage();
const user = page.props.auth.user;
const { state } = useSidebar();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button class="group/user flex w-full items-center gap-3 text-left outline-none transition-all"
                :class="{ 'justify-center': state === 'collapsed' }">
                <div class="relative">
                    <img :alt="user.name" class="h-8 w-8 rounded-full object-cover ring-2 ring-sidebar-border" :src="user.avatar ||
                        'https://ui-avatars.com/api/?name=' + user.name
                        " />
                    <div
                        class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-sidebar bg-emerald-500">
                    </div>
                </div>
                <div v-if="state === 'expanded'" class="flex min-w-0 flex-1 flex-col">
                    <p
                        class="truncate text-sm font-medium text-sidebar-foreground transition-colors group-hover/user:text-primary">
                        {{ user.name }}
                    </p>
                    <p class="truncate text-xs text-muted-foreground">
                        {{ user.email }}
                    </p>
                </div>
                <span v-if="state === 'expanded'"
                    class="material-symbols-outlined text-muted-foreground transition-colors group-hover/user:text-sidebar-foreground">
                    unfold_more
                </span>
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-56 rounded-lg bg-sidebar border-sidebar-border text-sidebar-foreground"
            align="end" :side-offset="8">
            <div class="flex items-center gap-2 p-2">
                <div class="flex flex-col space-y-1 leading-none">
                    <p class="font-medium">{{ user.name }}</p>
                    <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                </div>
            </div>
            <DropdownMenuSeparator class="bg-sidebar-border" />
            <DropdownMenuItem class="focus:bg-sidebar-accent focus:text-sidebar-accent-foreground cursor-pointer"
                as-child>
                <Link :href="profileEdit.url()" class="w-full flex items-center">
                    <User class="mr-2 h-4 w-4" />
                    <span>Profile</span>
                </Link>
            </DropdownMenuItem>
            <DropdownMenuItem class="focus:bg-sidebar-accent focus:text-sidebar-accent-foreground cursor-pointer"
                as-child>
                <Link :href="settingsRedirect.url()" class="w-full flex items-center">
                    <Settings class="mr-2 h-4 w-4" />
                    <span>Settings</span>
                </Link>
            </DropdownMenuItem>
            <DropdownMenuSeparator class="bg-sidebar-border" />
            <div class="px-2 py-2">
                <AppearanceTabsCompact />
            </div>
            <DropdownMenuSeparator class="bg-sidebar-border" />
            <DropdownMenuItem class="focus:bg-sidebar-accent focus:text-sidebar-accent-foreground cursor-pointer"
                as-child>
                <Link :href="logout.url()" method="post" as="button" class="w-full flex items-center">
                    <LogOut class="mr-2 h-4 w-4" />
                    <span>Log out</span>
                </Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
