import type {components} from "~/types/portainer-types";

export default defineEventHandler(() => {
  const config = useRuntimeConfig();

  return $fetch<components['schemas']['system.versionResponse']>(`${config.PORTAINER_BASE_URL}/api/system/version`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'X-API-Key': config.PORTAINER_X_API_KEY,
    },
  });
})
