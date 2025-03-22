Check if portainer has an update available
**Access policy**: authenticated

```toml
name = 'Check for portainer updates'
description = '/system/version'
method = 'GET'
url = '{{baseUrl}}/system/version'
sortWeight = 1000000
id = 'eba67796-a101-4d04-9da7-f81ea0139206'
```
