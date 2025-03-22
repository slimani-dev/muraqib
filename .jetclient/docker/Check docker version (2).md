```toml
name = 'Check docker version (2)'
method = 'GET'
url = '{{appUrl}}/portainer/endpoints/{environmentId}/docker/images/{imageId}/version'
sortWeight = 4000000
id = '71e8050a-0861-48f5-a818-059c3cbce9ac'

[[pathVariables]]
key = 'environmentId'
value = '1'

[[pathVariables]]
key = 'imageId'
value = 'sha256:bfd56db49c53a1a4c4e204de4f60462d2176b9f2bb130f0e56aa4ff04ae4bea4'
```

#### Post-response Script

```js
// jc.testCase("User API Tests", () => {
//     // Multiple test functions
//     jc.test("Status should be OK", () => {
//         jc.response.to.have.status(200)
//     })
//
//     jc.test("Response should have user data", () => {
//         jc.response.to.have.jsonBody("token")
//         jc.response.to.have.jsonBody("manifestUrl")
//     })
// })
//
// jc.globals.set('token', jc.response.json('token'))
// jc.globals.set('manifestUrl', jc.response.json('manifestUrl'))
// jc.globals.set('tag', jc.response.json('tag'))

```
