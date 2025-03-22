import type {components} from "~/types/portainer-types";
import type {Endpoint} from "~/types";

export default defineEventHandler(async () => {
  const config = useRuntimeConfig();

  const endpoints = await $fetch<components["schemas"]["portainer.Endpoint"][]>(`${config.PORTAINER_BASE_URL}/api/endpoints`, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'X-API-Key': config.PORTAINER_X_API_KEY,
    },
  });


  return endpoints.map((endpoint, index): Endpoint => {
    const {
      Id,
      Name,
      PublicURL,
      Gpus,
      Snapshots
    } = endpoint;

    const {
      DockerVersion,
      Swarm,
      TotalCPU,
      TotalMemory,
      ContainerCount,
      RunningContainerCount,
      StackCount,
      DockerSnapshotRaw
    } = Snapshots?.[0] ?? {};

    const DockerSnapshotInfo = DockerSnapshotRaw?.Info as {
      OperatingSystem?: string;
      OSVersion?: string;
      OSType?: string;
      Architecture?: string
    } | undefined;


    return {
      id: Id ?? index,
      Name: Name ? Name : 'Portainer ' + Id,
      PublicURL,
      Swarm: Swarm ? 1 : 0,
      TotalCPU: TotalCPU || 0,
      TotalMemory: TotalMemory || 0,
      Gpus: JSON.stringify(Gpus?.map((gpu) => gpu.name)),
      ContainerCount: ContainerCount || 0,
      RunningContainerCount: RunningContainerCount || 0,
      StackCount: StackCount || 0,
      DockerVersion: DockerVersion || "unknown",
      OperatingSystem: DockerSnapshotInfo?.OperatingSystem || "unknown",
      OSVersion: DockerSnapshotInfo?.OSVersion || "unknown",
      OSType: DockerSnapshotInfo?.OSType || "unknown",
      Architecture: DockerSnapshotInfo?.Architecture || "unknown",
    }
  })
})
