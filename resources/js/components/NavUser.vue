<script setup lang="ts">
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useSidebar } from '@/components/ui/sidebar/utils';
import { logout } from '@/routes';
import { usePage, Link } from '@inertiajs/vue3';
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
                    <img :alt="user.name" class="h-8 w-8 rounded-full object-cover ring-2 ring-slate-800" :src="user.avatar ||
                        'https://ui-avatars.com/api/?name=' + user.name
                        " />
                    <div
                        class="absolute bottom-0 right-0 h-2.5 w-2.5 rounded-full border-2 border-sidebar-dark bg-emerald-500">
                    </div>
                </div>
                <div v-if="state === 'expanded'" class="flex min-w-0 flex-1 flex-col">
                    <p class="truncate text-sm font-medium text-white transition-colors group-hover/user:text-primary">
                        {{ user.name }}
                    </p>
                    <p class="truncate text-xs text-slate-500">
                        {{ user.email }}
                    </p>
                </div>
                <span v-if="state === 'expanded'"
                    class="material-symbols-outlined text-slate-500 transition-colors group-hover/user:text-white">
                    unfold_more
                </span>
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-56 rounded-lg bg-sidebar-dark border-slate-800 text-white" align="end"
            :side-offset="8">
            <div class="flex items-center gap-2 p-2">
                <div class="flex flex-col space-y-1 leading-none">
                    <p class="font-medium">{{ user.name }}</p>
                    <p class="text-xs text-slate-400">{{ user.email }}</p>
                </div>
            </div>
            <DropdownMenuSeparator class="bg-slate-800" />
            <DropdownMenuItem class="focus:bg-surface-dark focus:text-white cursor-pointer">
                <User class="mr-2 h-4 w-4" />
                <span>Profile</span>
            </DropdownMenuItem>
            <DropdownMenuItem class="focus:bg-surface-dark focus:text-white cursor-pointer">
                <Settings class="mr-2 h-4 w-4" />
                <span>Settings</span>
            </DropdownMenuItem>
            <DropdownMenuSeparator class="bg-slate-800" />
            <DropdownMenuItem class="focus:bg-surface-dark focus:text-white cursor-pointer" as-child>
                <Link :href="logout.url()" method="post" as="button" class="w-full flex items-center">
                    <LogOut class="mr-2 h-4 w-4" />
                    <span>Log out</span>
                </Link>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
