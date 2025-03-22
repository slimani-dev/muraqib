```toml
name = 'Check docker version'
method = 'GET'
url = '{{baseUrl}}/endpoints/{environmentId}/docker/images/{imageId}/json'
sortWeight = 2000000
id = '1e8bad2d-6684-484a-8c0a-22a451f07ab6'

[[pathVariables]]
key = 'environmentId'
value = '1'

[[pathVariables]]
key = 'imageId'
value = 'sha256:53cfaf28eae24189b0a73f140d8a439b8d0b2e349470a675e9fe12edc0dcc745'
```
