<script setup lang="ts">
import CloudflareWizard from '@/components/Settings/Infrastructure/CloudflareWizard.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

defineProps<{
  tunnels: { status: string; connections: number; details: any }[] | [];
}>();
</script>

<template>
  <Card class="border-slate-200 bg-white backdrop-blur-sm dark:border-slate-800 dark:bg-surface-dark/40">
    <CardHeader class="flex flex-row items-center justify-between border-b border-border p-4">
      <div class="flex items-center gap-3">
        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-[#F38020]/10 text-[#F38020]">
          <span class="material-symbols-outlined">cloud</span>
        </div>
        <div>
          <CardTitle class="text-base">Cloudflare Tunnel</CardTitle>
          <CardDescription>Securely expose services without opening ports.</CardDescription>
        </div>
      </div>
      <div class="flex gap-2">
        <!-- Wizard Trigger -->
        <CloudflareWizard>
          <Button type="button" variant="default" size="sm">
            <span class="material-symbols-outlined mr-2">bolt</span>
            Setup / Manage Tunnel
          </Button>
        </CloudflareWizard>
      </div>
    </CardHeader>
    <!-- Tunnel Details -->
    <CardContent v-if="tunnels.length > 0" class="p-0">
        <div v-for="(tunnel, index) in tunnels" :key="tunnel.details.id" :class="cn('grid grid-cols-2 md:grid-cols-4 gap-6 p-6', index !== 0 && 'border-t')">
            <div class="space-y-1">
              <div class="text-[10px] font-semibold tracking-wider text-muted-foreground uppercase">Tunnel Name</div>
              <div class="text-sm font-medium flex items-center gap-2">
                  <span class="h-2 w-2 rounded-full bg-emerald-500 inline-block" v-if="tunnel.status === 'healthy'"></span>
                  <span class="h-2 w-2 rounded-full bg-amber-500 inline-block" v-else></span>
                  {{ tunnel.details.name }}
              </div>
            </div>
            <div class="space-y-1">
              <div class="text-[10px] font-semibold tracking-wider text-muted-foreground uppercase">Tunnel ID</div>
              <div class="truncate font-mono text-xs text-muted-foreground bg-muted p-1 rounded w-fit max-w-full" :title="tunnel.details.id">
                {{ tunnel.details.id }}
              </div>
            </div>
            <div class="space-y-1">
              <div class="text-[10px] font-semibold tracking-wider text-muted-foreground uppercase">Created At</div>
              <div class="text-sm text-foreground/80">{{ new Date(tunnel.details.created_at).toLocaleDateString() }}</div>
            </div>
            <div class="space-y-1">
              <div class="text-[10px] font-semibold tracking-wider text-muted-foreground uppercase">Region</div>
              <div class="flex items-center gap-1.5 text-sm text-foreground/80">
                <span class="material-symbols-outlined text-[16px] text-muted-foreground">public_off</span>
                {{ tunnel.details.conns?.[0]?.colo || 'Unknown' }}
              </div>
            </div>
        </div>
    </CardContent>
    
    <!-- Empty State -->
    <CardContent v-else class="p-6">
      <div class="flex items-center justify-between rounded-lg border border-dashed p-4 bg-muted/30">
        <div class="flex items-center gap-4">
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-background border shadow-sm">
            <span class="material-symbols-outlined text-muted-foreground">public</span>
          </div>
          <div>
            <p class="text-sm font-medium">No Active Tunnels</p>
            <p class="text-xs text-muted-foreground">Use the wizard to configure your first secure tunnel.</p>
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
