**Access policy**:

```toml
name = 'Fetch images'
description = '/docker/{environmentId}/images'
method = 'GET'
url = '{{baseUrl}}/docker/{environmentId}/images'
sortWeight = 1000000
id = '0436a1e9-d3ed-4582-aaf8-5b2a9092f6c8'

[[queryParams]]
key = 'withUsage'
description = 'Include image usage information'
disabled = true

[[pathVariables]]
key = 'environmentId'
value = '1'
```

#### Post-response Script

```js
console.log(jc.response.json().map(i => ({id: i.id})))

```
