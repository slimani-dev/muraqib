export interface Endpoint {
  id: number;
  Name: string;
  PublicURL?: string;
  Swarm: number;
  TotalCPU: number;
  TotalMemory: number;
  Gpus: string; // JSON string of GPU names
  ContainerCount: number;
  RunningContainerCount: number;
  StackCount: number;
  DockerVersion: string;
  OperatingSystem: string;
  OSVersion: string;
  OSType: string;
  Architecture: string;
}

export interface Stack {
  id,
  label,
  endpointId,
  status,
  icon
}

type ContainerInfo = {
  id: string;
  name: string;
  image: string;
  imageId: string;
  state: string;
  status: string;
  stack?: string;
  muraqib_description?: string;
  muraqib_icon?: string;
  muraqib_name?: string;
  muraqib_url?: string;
};

export type Container = {
  Id: string;
  Names: string[];
  Image: string;
  ImageID: string;
  Command: string;
  Created: number;
  Ports: {
    IP?: string;
    PrivatePort: number;
    PublicPort?: number;
    Type: string;
  }[];
  Labels: {
    [key: string]: string;
  };
  State: string;
  Status: string;
  HostConfig: {
    NetworkMode: string;
  };
  NetworkSettings: {
    Networks: {
      [networkName: string]: {
        IPAMConfig?: unknown;
        Links?: unknown;
        Aliases?: string[];
        NetworkID: string;
        EndpointID: string;
        Gateway: string;
        IPAddress: string;
        IPPrefixLen: number;
        IPv6Gateway: string;
        GlobalIPv6Address: string;
        GlobalIPv6PrefixLen: number;
        MacAddress: string;
      };
    };
  };
  Mounts: {
    Type: string;
    Name?: string;
    Source: string;
    Destination: string;
    Driver?: string;
    Mode: string;
    RW: boolean;
    Propagation: string;
  }[];
};

