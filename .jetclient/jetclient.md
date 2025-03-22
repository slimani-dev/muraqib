```toml
name = 'Tahakom - Portainer'
id = '5e709b4b-16dc-4715-9928-e9a9d76b502f'

[[environmentGroups]]
name = 'Default'

[auth.apiKey]
key = 'X-API-Key'
value = '{{X-API-Key}}'
inHeader = true

[[apis]]
name = 'API'
[apis.openApi]
file = 'types/portainer-openapi3.yaml'
```

#### Variables

```json5
{
  globals: {
    appUrl: "http://localhost:3000/api",
    baseUrl: "http://192.168.1.200:9000/api",
    "X-API-Key": "xxx",
    manifestUrl: "",
    token: ""
  }
}
```
