<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
</script>

<template>

    <Head title="Global Settings & Vault" />

    <AuthLayout :breadcrumbs="[{ title: 'Settings', href: '/settings' }]">
        <div class="relative flex min-h-0 flex-1 flex-col overflow-hidden">
            <!-- Page Header -->
            <div class="flex-none border-b border-border bg-background/50 backdrop-blur-md px-8 py-6 z-10">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-foreground">
                            Global Settings & Vault
                        </h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Manage system defaults, secure API keys, and external integrations.
                        </p>
                    </div>
                    <div class="flex gap-3">
                        <Button variant="outline">Discard</Button>
                        <Button>Save Changes</Button>
                    </div>
                </div>
            </div>

            <!-- Scrollable Content -->
            <div class="flex-1 overflow-y-auto w-full p-8">
                <div class="mx-auto max-w-5xl space-y-8">
                    <!-- Section: Global Defaults -->
                    <section class="space-y-4">
                        <div class="flex items-center justify-between pb-2 border-b border-border">
                            <h3 class="text-lg font-medium leading-6 text-foreground">Global Defaults</h3>
                        </div>

                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div class="grid w-full items-center gap-1.5">
                                <Label for="root-domain">Root Domain</Label>
                                <div class="flex w-full items-center">
                                    <span
                                        class="flex h-10 items-center rounded-l-md border border-r-0 border-input bg-muted px-3 text-sm text-muted-foreground">
                                        https://
                                    </span>
                                    <Input id="root-domain" type="text" model-value="lab.example.com"
                                        class="rounded-l-none" />
                                </div>
                                <p class="text-xs text-muted-foreground">Base domain for all generated proxy hosts.</p>
                            </div>

                            <div class="grid w-full items-center gap-1.5">
                                <Label for="admin-email">Admin Email</Label>
                                <Input id="admin-email" type="email" model-value="admin@muraqib.io" />
                                <p class="text-xs text-muted-foreground">Used for Let's Encrypt notifications and
                                    alerts.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 pt-2">
                            <span class="text-sm font-medium text-muted-foreground">Theme Preference</span>
                            <div class="flex rounded-lg bg-muted p-1">
                                <button
                                    class="rounded-md px-3 py-1.5 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
                                    Light
                                </button>
                                <button
                                    class="rounded-md bg-background px-3 py-1.5 text-sm font-medium text-primary shadow-sm ring-1 ring-border">
                                    Dark
                                </button>
                                <button
                                    class="rounded-md px-3 py-1.5 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors">
                                    System
                                </button>
                            </div>
                        </div>
                    </section>

                    <!-- Section: API Vault -->
                    <section class="space-y-6 pt-4">
                        <div class="flex items-center justify-between pb-2 border-b border-border">
                            <h3 class="text-lg font-medium leading-6 text-foreground">API Vault</h3>
                            <Badge variant="outline"
                                class="bg-emerald-500/10 text-emerald-500 border-emerald-500/20 gap-1.5">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                All Systems Operational
                            </Badge>
                        </div>

                        <!-- Cloudflare Card -->
                        <Card class="bg-surface-dark/40 border-slate-800 backdrop-blur-sm">
                            <CardHeader class="flex flex-row items-center justify-between border-b border-border p-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#F38020]/10 text-[#F38020]">
                                        <span class="material-symbols-outlined">cloud</span>
                                    </div>
                                    <div>
                                        <CardTitle class="text-base">Cloudflare DNS</CardTitle>
                                        <CardDescription>Managing DNS records for automatic SSL.</CardDescription>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge variant="outline"
                                        class="bg-emerald-500/10 text-emerald-500 border-emerald-500/20">Active</Badge>
                                    <Button variant="ghost" size="sm"
                                        class="text-primary hover:text-primary/80 hover:bg-primary/10">
                                        Test Connection
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-2">
                                <div class="space-y-1">
                                    <Label class="text-xs font-medium uppercase text-muted-foreground">Account
                                        ID</Label>
                                    <Input type="text" model-value="a1b2c3d4e5f6g7h8i9j0" class="font-mono" />
                                </div>
                                <div class="space-y-1 relative">
                                    <Label class="text-xs font-medium uppercase text-muted-foreground">API Token</Label>
                                    <div class="relative">
                                        <Input type="password" model-value="super-secret-token-value-here-123"
                                            class="font-mono pr-20" />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-2">
                                            <Button variant="ghost" size="icon"
                                                class="h-8 w-8 text-muted-foreground hover:text-foreground">
                                                <span class="material-symbols-outlined text-[18px]">visibility</span>
                                            </Button>
                                            <Button variant="ghost" size="icon"
                                                class="h-8 w-8 text-muted-foreground hover:text-foreground">
                                                <span class="material-symbols-outlined text-[18px]">content_copy</span>
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Proxmox Card -->
                        <Card class="bg-surface-dark/40 border-slate-800 backdrop-blur-sm">
                            <CardHeader class="flex flex-row items-center justify-between border-b border-border p-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#E57000]/10 text-[#E57000]">
                                        <span class="material-symbols-outlined">dataset</span>
                                    </div>
                                    <div>
                                        <CardTitle class="text-base">Proxmox VE</CardTitle>
                                        <CardDescription>Hypervisor cluster management.</CardDescription>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge variant="outline"
                                        class="bg-emerald-500/10 text-emerald-500 border-emerald-500/20">Connected
                                    </Badge>
                                    <Button variant="ghost" size="sm"
                                        class="text-primary hover:text-primary/80 hover:bg-primary/10">
                                        Test Connection
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent class="grid grid-cols-1 gap-6 p-6 lg:grid-cols-3">
                                <div class="space-y-1">
                                    <Label class="text-xs font-medium uppercase text-muted-foreground">Host URL</Label>
                                    <Input type="text" model-value="https://192.168.1.5:8006" class="font-mono" />
                                </div>
                                <div class="space-y-1">
                                    <Label class="text-xs font-medium uppercase text-muted-foreground">Token ID</Label>
                                    <Input type="text" model-value="root@pam!terraform" class="font-mono" />
                                </div>
                                <div class="space-y-1 relative">
                                    <Label class="text-xs font-medium uppercase text-muted-foreground">Secret</Label>
                                    <div class="relative">
                                        <Input type="password" model-value="proxmox-secret-key-uuid"
                                            class="font-mono pr-10" />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-2">
                                            <Button variant="ghost" size="icon"
                                                class="h-8 w-8 text-muted-foreground hover:text-foreground">
                                                <span
                                                    class="material-symbols-outlined text-[18px]">visibility_off</span>
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- Portainer Card -->
                        <Card class="bg-surface-dark/40 border-slate-800 backdrop-blur-sm">
                            <CardHeader class="flex flex-row items-center justify-between border-b border-border p-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#0092c2]/10 text-[#0092c2]">
                                        <span class="material-symbols-outlined">dock</span>
                                    </div>
                                    <div>
                                        <CardTitle class="text-base">Portainer</CardTitle>
                                        <CardDescription>Docker container orchestration.</CardDescription>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Badge variant="secondary" class="bg-muted text-muted-foreground">Unverified</Badge>
                                    <Button variant="ghost" size="sm"
                                        class="text-primary hover:text-primary/80 hover:bg-primary/10">
                                        Test Connection
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent class="p-6">
                                <div class="space-y-1 relative">
                                    <Label class="text-xs font-medium uppercase text-muted-foreground">Access
                                        Token</Label>
                                    <div class="relative">
                                        <Input type="password" placeholder="ptr_..." class="font-mono pr-10" />
                                        <div class="absolute inset-y-0 right-0 flex items-center pr-2">
                                            <Button variant="ghost" size="icon"
                                                class="h-8 w-8 text-muted-foreground hover:text-foreground">
                                                <span
                                                    class="material-symbols-outlined text-[18px]">visibility_off</span>
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </section>

                    <!-- Section: Template Sources -->
                    <section class="space-y-4 pt-4 pb-12">
                        <div class="flex items-center justify-between pb-2 border-b border-border">
                            <h3 class="text-lg font-medium leading-6 text-foreground">Template Sources</h3>
                            <Button size="sm" variant="ghost"
                                class="bg-primary/10 text-primary hover:bg-primary/20 hover:text-primary">
                                <span class="material-symbols-outlined text-[16px] mr-1">add</span>
                                Add Repository
                            </Button>
                        </div>

                        <div
                            class="overflow-hidden rounded-xl border border-border bg-surface-dark/40 backdrop-blur-sm">
                            <table class="min-w-full divide-y divide-border">
                                <thead class="bg-muted/50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                            Name</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                            Repository URL</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                            Branch</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-muted-foreground">
                                            Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border bg-transparent">
                                    <tr>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm font-medium text-foreground">Official Templates</div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div
                                                class="flex items-center gap-2 text-sm text-muted-foreground font-mono">
                                                <span class="material-symbols-outlined text-[16px]">code</span>
                                                github.com/muraqib/templates
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <Badge variant="outline"
                                                class="font-mono bg-muted text-foreground border-border">main</Badge>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <Button variant="ghost" size="sm"
                                                class="text-primary hover:text-primary/80 hover:bg-primary/10 mr-2">Edit</Button>
                                            <Button variant="ghost" size="sm"
                                                class="text-destructive hover:text-destructive hover:bg-destructive/10">Delete</Button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm font-medium text-foreground">Community Add-ons</div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div
                                                class="flex items-center gap-2 text-sm text-muted-foreground font-mono">
                                                <span class="material-symbols-outlined text-[16px]">code</span>
                                                github.com/linuxserver/docker-templates
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <Badge variant="outline"
                                                class="font-mono bg-muted text-foreground border-border">master</Badge>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <Button variant="ghost" size="sm"
                                                class="text-primary hover:text-primary/80 hover:bg-primary/10 mr-2">Edit</Button>
                                            <Button variant="ghost" size="sm"
                                                class="text-destructive hover:text-destructive hover:bg-destructive/10">Delete</Button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div class="text-sm font-medium text-foreground">Local Homelab</div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <div
                                                class="flex items-center gap-2 text-sm text-muted-foreground font-mono">
                                                <span class="material-symbols-outlined text-[16px]">dns</span>
                                                gitea.local/ops/compose-files
                                            </div>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <Badge variant="outline"
                                                class="font-mono bg-muted text-foreground border-border">production
                                            </Badge>
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                            <Button variant="ghost" size="sm"
                                                class="text-primary hover:text-primary/80 hover:bg-primary/10 mr-2">Edit</Button>
                                            <Button variant="ghost" size="sm"
                                                class="text-destructive hover:text-destructive hover:bg-destructive/10">Delete</Button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthLayout>
</template>
