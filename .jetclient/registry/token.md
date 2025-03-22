```toml
name = 'token'
method = 'GET'
url = 'https://auth.docker.io/token?service=registry.docker.io&scope=repository:fallenbagel/jellyseerr:pull'
sortWeight = 1000000
id = 'ecac8828-efa8-46a8-bff2-c06c7c8add5c'

[[queryParams]]
key = 'service'
value = 'registry.docker.io'

[[queryParams]]
key = 'scope'
value = 'repository:fallenbagel/jellyseerr:pull'
```

#### Post-response Script

```js
jc.globals.set('token', jc.response.json('token'))

```
