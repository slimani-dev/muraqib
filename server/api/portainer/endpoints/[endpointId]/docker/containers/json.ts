import type {Container} from "~/types";

export default defineEventHandler(async (event) => {

  const config = useRuntimeConfig();
  const {endpointId} = getRouterParams(event)

  const url = `${config.PORTAINER_BASE_URL}/api/endpoints/${endpointId}/docker/containers/json`;
  const containers = await $fetch<Container[]>(url, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'X-API-Key': config.PORTAINER_X_API_KEY,
    },
  })

  return containers
      .filter((container: Container) => container.Labels['muraqib.active'] === 'true')
      .map((container: Container) => {
        return {
          id: container.Id,
          name: container.Names?.[0]?.replace(/^\//, "") || "Unknown",
          image: container.Image,
          imageId: container.ImageID,
          state: container.State,
          status: container.Status,
          stack: container.Labels['com.docker.compose.project'],
          muraqib_description: container.Labels['muraqib.description'],
          muraqib_icon: container.Labels['muraqib.icon'],
          muraqib_name: container.Labels['muraqib.name'],
          muraqib_url: container.Labels['muraqib.url'],
        }
      })
});
