// Extract registry, image name, and tag from an image reference

const parseImageRef = (imageRef: string) => {
  // Function to get the tag from an image reference
  const getTagFromImageRef = (imageRef: string): string => {
    const regex = /:([a-zA-Z0-9._-]+)/;
    const match = imageRef.match(regex);
    return match ? match[1] : "latest"; // Default to "latest" if no tag is found
  };

  // Function to get the registry from an image reference
  const getRegistryFromImageRef = (imageRef: string): string => {
    const regex = /^([a-zA-Z0-9.-]+(?:\.[a-zA-Z0-9.-]+)*)(?=\/)/;
    const match = imageRef.match(regex);
    return match ? match[1] : "docker.io"; // Default to "docker.io" if no registry is found
  };

  const getImageNameFromImageRef = (imageRef: string): string => {
    const regex = /^(?:[a-zA-Z0-9.-]+(?:\.[a-zA-Z0-9.-]+)*\/)?([^:]+)/;
    const match = imageRef.match(regex);
    return match ? match[1] : imageRef; // Return the image name part
  };

  const tag = getTagFromImageRef(imageRef);
  const registry = getRegistryFromImageRef(imageRef);
  const imageName = getImageNameFromImageRef(imageRef);

  return {registry, imageName, tag};
};

const getDockerAuthUrl = (imageData: {
  registry: string;
  imageName: string;
}): string => {
  const {registry, imageName} = imageData;

  if (registry === "docker.io") {
    return `https://auth.docker.io/token?service=registry.docker.io&scope=repository:${imageName}:pull`;
  }

  if (registry === "lscr.io") {
    return `https://ghcr.io/token?service=ghcr.io&scope=repository:${imageName.replace(
        "lscr.io/",
        ""
    )}:pull`;
  }

  if (registry === "ghcr.io") {
    return `https://ghcr.io/token?service=ghcr.io&scope=repository:${imageName}:pull`;
  }

  if (registry === "quay.io") {
    return `https://quay.io/v2/auth?service=quay.io&scope=repository:${imageName}:pull`;
  }

  return `https://auth.${registry}/token?service=registry.${registry}&scope=repository:${imageName.replace(
      `${registry}/`,
      ""
  )}:pull`;
};


const getImageManifest = async (imageData: {
  registry: string;
  imageName: string;
  tag: string;
  token: string
}): Promise<{
  manifests: {
    platform: {
      os: string,
      architecture: string,
      variant: string,
    }
    digest: string
  }[];
  config: {
    digest: string
  }
}> => {
  const {registry, imageName, tag, token} = imageData;


  const repositoryName = imageName.replace('lscr.io/', '')
      .replace('ghcr.io/', '')

  let manifestUrl: string;

  if (registry === "docker.io") {
    manifestUrl = `https://registry-1.${registry}/v2/${repositoryName}/manifests/${tag}`;
  } else {
    manifestUrl = `https://${registry}/v2/${repositoryName}/manifests/${tag}`;
  }

  console.log({manifestUrl})

  return await $fetch<never>(manifestUrl, {
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: "application/vnd.oci.image.index.v1+json, application/vnd.docker.distribution.manifest.list.v2+json, application/vnd.oci.image.manifest.v1+json, application/vnd.docker.distribution.manifest.v2+json",
    },
  })
};


export default defineEventHandler(async (event) => {

  const config = useRuntimeConfig();
  const {imageId, endpointId} = getRouterParams(event)

  const url = `${config.PORTAINER_BASE_URL}/api/endpoints/${endpointId}/docker/images/${imageId}/json`;
  const imageData = await $fetch<{
    Id: string,
    RepoTags: string[],
    RepoDigests: string[],
    Created: string,
  }>(url, {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
      'X-API-Key': config.PORTAINER_X_API_KEY,
    },
  })

  const imageRef = imageData.RepoTags[0];
  const {registry, imageName, tag} = parseImageRef(imageRef);

  console.log({registry, imageName, tag})

  // Determine auth URL
  const authUrl = getDockerAuthUrl({registry, imageName})

  console.log({authUrl})

  // Get token
  const tokenResponse = await $fetch<{ token: string }>(authUrl);
  const token = tokenResponse.token;

  console.log({token})

  const {manifests} = await getImageManifest({
    imageName,
    registry,
    tag,
    token
  })

  const architecture = 'amd64';
  const os = 'linux';
  const variant = ''

  // TODO get architecture and os from endpoint

  // const endpoint = await $fetch<components["schemas"]["portainer.Endpoint"]>(`${config.PORTAINER_BASE_URL}/api/endpoints/${endpointId}`, {
  //   method: 'GET',
  //   headers: {
  //     'Content-Type': 'application/json',
  //     'X-API-Key': config.PORTAINER_X_API_KEY,
  //   },
  // });

  const digest = manifests.find((m: {
    platform: {
      os: string,
      architecture: string,
      variant: string,
    }
    digest: string
  }) => {
    let flag = m.platform.os === os && m.platform.architecture === architecture;
    if (variant && m.platform.variant) {
      flag = flag && m.platform.variant === variant
    }

    return flag
  })?.digest

  if (!digest) {
    throw new Error('No digest found')
  }

  const {config: imageConfig} = await getImageManifest({
    imageName,
    registry,
    tag: digest,
    token
  })

  console.log({
    imageRef,
    imageName,
    registry,
    tag,
    imageId,
    digest,
  })

  return imageConfig.digest
});
