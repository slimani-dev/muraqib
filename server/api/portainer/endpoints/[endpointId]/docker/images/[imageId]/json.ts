import type {components} from "~/types/portainer-types";

export default defineEventHandler((event) => {

  const config = useRuntimeConfig();
  const {imageId, endpointId} = getRouterParams(event)

  const url = `${config.PORTAINER_BASE_URL}/endpoints/${endpointId}/docker/images/${imageId}/json`;
  return $fetch<components['schemas']['system.versionResponse']>(url, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'X-API-Key': config.PORTAINER_X_API_KEY,
    },
  })
});
