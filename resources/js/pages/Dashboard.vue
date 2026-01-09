<script setup lang="ts">
import WelcomeSection from '@/components/dashboard/WelcomeSection.vue';
import ActivePRsCard from '@/components/dashboard/widgets/ActivePRsCard.vue';
import MediaCard from '@/components/dashboard/widgets/MediaCard.vue';
import NetworkCard from '@/components/dashboard/widgets/NetworkCard.vue';
import ProxmoxCard from '@/components/dashboard/widgets/ProxmoxCard.vue';
import RequestsCard from '@/components/dashboard/widgets/RequestsCard.vue';
import PortainerStacksStatsCard from '@/components/portainer/widgets/PortainerStacksStatsCard.vue';
import PortainerContainersStatsCard from '@/components/portainer/widgets/PortainerContainersStatsCard.vue';
import PortainerUpdatesPendingCard from '@/components/portainer/widgets/PortainerUpdatesPendingCard.vue';
import PortainerApiConnectionCard from '@/components/portainer/widgets/PortainerApiConnectionCard.vue';
import PortainerStacks from '@/components/portainer/widgets/PortainerStacks.vue';
import DockerEventsWidget from '@/components/portainer/widgets/DockerEventsWidget.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import { dashboard } from '@/routes';
</script>

<template>

  <Head title="Dashboard" />

  <AppLayout :breadcrumbs="[{ title: 'Dashboard', href: dashboard.url() }]">
    <div class="relative flex min-h-0 flex-1 flex-col overflow-hidden">
      <!-- Scrollable Content Area -->
      <div class="flex-1 overflow-y-auto scroll-smooth p-6">
        <div class="mx-auto flex flex-col gap-6">
          <!-- Welcome & Actions Row -->
          <WelcomeSection />

          <!-- Portainer / Container Section -->
          <div class="flex flex-col gap-4">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Container Infrastructure</h2>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 2xl:grid-cols-4 gap-4">
              <PortainerStacksStatsCard />
              <PortainerContainersStatsCard />
              <PortainerUpdatesPendingCard />
              <PortainerApiConnectionCard />
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
              <!-- Active Stacks (Left / Top) -->
              <div class="lg:col-span-8 flex flex-col gap-4">
                <PortainerStacks />
              </div>

              <!-- Docker Events (Right / Bottom) -->
              <div class="lg:col-span-4 flex flex-col gap-4">
                <DockerEventsWidget />
              </div>
            </div>
          </div>

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
  </AppLayout>
</template>
