Retrieve details about an environment(endpoint).
**Access policy**: restricted

```toml
name = 'Inspect an environment(endpoint)'
description = '/endpoints/{id}'
method = 'GET'
url = '{{baseUrl}}/endpoints/{id}'
sortWeight = 2000000
id = '1516fb23-56b6-4fb6-b86b-0dcf08e9a02a'

[[pathVariables]]
key = 'id'
value = '1 '
description = 'Environment(Endpoint) identifier'
```
