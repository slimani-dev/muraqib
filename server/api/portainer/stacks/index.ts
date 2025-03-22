import type {components} from "~/types/portainer-types";
import type {Stack} from "~/types";

export default defineEventHandler(async () => {
  const config = useRuntimeConfig();

  const stacks = await $fetch<components["schemas"]["portainer.Stack"][]>(`${config.PORTAINER_BASE_URL}/api/stacks`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'X-API-Key': config.PORTAINER_X_API_KEY,
    },
  });


  return stacks.filter((stack) => {
    const hide = stack.Env?.find(E => E.name === 'TAHAKOM_HIDE')?.value ?? 'false'
    return hide.toLowerCase() === 'false'
  }).map((stack): Stack => {
    const {
      Id,
      Name,
      EndpointId,
      Status,
      Env
    } = stack;

    return {
      id: Id,
      label: Name,
      endpointId: EndpointId,
      status: Status,
      icon: Env?.find(E => E.name === 'TAHAKOM_ICON')?.value ?? 'i-mdi-layers-triple-outline',
    }
  })
})
