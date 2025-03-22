<script setup lang="ts">
import type {components} from "~/types/portainer-types";

const {
  data: versionResponse
} = useFetch<components['schemas']['system.versionResponse']>('/api/portainer/system/version', {
  lazy: true,
  server: false,

})

const dismissed = ref(true);

onMounted(() => {
  // Retrieve dismissed from localStorage
  const localstorageDismissed = localStorage.getItem('version_check_dismissed');
  console.log('localstorageDismissed', localstorageDismissed, localstorageDismissed && localstorageDismissed === 'TRUE')
  if (localstorageDismissed && localstorageDismissed === 'TRUE') {
    dismissed.value = true;
  } else {
    dismissed.value = false;
  }

  console.log('dismissed.value', dismissed.value)
});

// Watch for new version updates
watch(versionResponse, () => {
  if (versionResponse.value?.UpdateAvailable) {
    dismissed.value = false; // Reset if a new version is detected
    localStorage.removeItem('version_check_dismissed');
  }
});

// Function to dismiss the up-to-date alert
const dismissAlert = () => {
  console.log('dismissAlert')
  dismissed.value = true;
  localStorage.setItem('version_check_dismissed', 'TRUE');
};

</script>
<template>
  <UAlert
      v-if="versionResponse?.UpdateAvailable && !dismissed"
      color="warning"
      :title="`Update Available! v${versionResponse?.LatestVersion}`"
      icon="i-mdi-reload-alert"
      :description="`Current version ${versionResponse?.ServerVersion}`"
      variant="outline"
      :actions="[
        {
          label: 'Release Note',
          color: 'neutral',
          variant: 'subtle',
          icon: 'i-mdi-github',
          to: `https://github.com/portainer/portainer/releases/tag/${versionResponse?.LatestVersion}`,
          target: '_blank'
        }
      ]"
      close
      @update:open="dismissAlert"
  />
  <UAlert
      v-else-if="!dismissed"
      color="success"
      title="Portainer is up to date"
      icon="i-mdi-check-circle"
      :description="`Version ${versionResponse?.ServerVersion} ${versionResponse?.VersionSupport	}`"
      variant="outline"
      close
      @update:open="dismissAlert"
  />
</template>
