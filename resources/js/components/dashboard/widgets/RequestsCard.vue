<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
</script>

<template>
  <Card class="flex h-full flex-col">
    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4">
      <div class="flex items-center gap-3">
        <span class="material-symbols-outlined text-amber-500">notifications_active</span>
        <CardTitle>Requests</CardTitle>
      </div>
      <span class="flex h-2 w-2 rounded-full bg-amber-500"></span>
    </CardHeader>
    <CardContent class="flex flex-1 flex-col gap-3">
      <div class="flex gap-3 rounded-lg border border-border bg-muted/50 p-3">
        <img alt="Poster for The Bear" class="h-14 w-10 shrink-0 rounded object-cover"
          src="https://lh3.googleusercontent.com/aida-public/AB6AXuCgsB6TkAeReSzFkMIzl9kEbo6hzlbFnXcNvP8lwwBypAzI7BIY4QDEQpWkGHaMeYP3ZV8EfRWpsX_PtMmzipJKtH5vgHQ1IE87UprvpzQCKuwsLTpqQ-c5vjmzzxleECBazgLj8uY2ylcSZjGWmntSfhyqltifTpwF39aUcVZmt9l8bI34ttU34FbdwJYJEnjiwM1_JVkvveeAbKBr6BYxB6pf9cPbUE-SrQ29XBsM4Yupn4WBs_7IpLHyxHIxIuAaCRSOO8mXbcOH" />
        <div class="flex min-w-0 flex-1 flex-col justify-between">
          <div>
            <p class="truncate text-sm font-medium text-card-foreground">
              The Bear (Season 3)
            </p>
            <p class="text-xs text-muted-foreground">
              Requested by <span class="font-medium text-card-foreground">Ali</span>
            </p>
          </div>
          <div class="mt-2 flex gap-2">
            <Button size="sm" variant="success"
              class="h-auto flex-1 rounded-full py-1 text-[10px] font-bold tracking-wide uppercase">
              Approve
            </Button>
            <Button size="sm" variant="destructive"
              class="h-auto flex-1 rounded-full py-1 text-[10px] font-bold tracking-wide uppercase">
              Deny
            </Button>
          </div>
        </div>
      </div>
      <div class="mt-auto border-t border-border pt-2">
        <div class="flex items-center gap-2 text-xs text-muted-foreground">
          <span class="material-symbols-outlined text-sm text-green-500">check_circle</span>
          <span>Auto-approved: "Fallout" (4h ago)</span>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
