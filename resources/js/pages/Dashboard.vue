<script setup lang="ts">
import WelcomeSection from '@/components/dashboard/WelcomeSection.vue';
import ActivePRsCard from '@/components/dashboard/widgets/ActivePRsCard.vue';
import MediaCard from '@/components/dashboard/widgets/MediaCard.vue';
import NetworkCard from '@/components/dashboard/widgets/NetworkCard.vue';
import ProxmoxCard from '@/components/dashboard/widgets/ProxmoxCard.vue';
import RequestsCard from '@/components/dashboard/widgets/RequestsCard.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
  <Head title="Dashboard" />

  <AuthLayout :breadcrumbs="[{ title: 'Dashboard', href: '/dashboard' }]">
    <div class="relative flex min-h-0 flex-1 flex-col overflow-hidden">
      <!-- Scrollable Content Area -->
      <div class="flex-1 overflow-y-auto scroll-smooth p-6">
        <div class="mx-auto flex  flex-col gap-6">
          <!-- Welcome & Actions Row -->
          <WelcomeSection />

          <!-- Infrastructure Grid -->
          <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <!-- Proxmox: Span 2 cols -->
            <div class="lg:col-span-2">
              <ProxmoxCard />
            </div>
            <!-- Network: Span 1 col -->
            <div class="lg:col-span-1">
              <NetworkCard />
            </div>

            <!-- Media Widget -->
            <div>
              <MediaCard />
            </div>
            <!-- Active PRs Widget -->
            <div>
              <ActivePRsCard />
            </div>
            <!-- Requests Widget -->
            <div>
              <RequestsCard />
            </div>
          </div>
        </div>
        <!-- Bottom spacer -->
        <div class="h-10"></div>
      </div>
    </div>
  </AuthLayout>
</template>
