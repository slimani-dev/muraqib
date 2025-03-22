```toml
name = 'manifest (1) (1)'
method = 'GET'
url = 'https://registry-1.docker.io/v2/fallenbagel/jellyseerr/manifests/sha256:0a4fcca59ced764cce131dec0407d7bd8eeac114697bf12f451f422294c24b20'
sortWeight = 2500000
id = '90e1c791-39ce-400f-8acf-d5ca9ee08116'

[[headers]]
key = 'Authorization'
value = 'Bearer {{token}}'

[[headers]]
key = 'Accept'
value = 'application/vnd.oci.image.index.v1+json, application/vnd.docker.distribution.manifest.list.v2+json'
```

#### Post-response Script

```js
jc.testCase('User API Tests', () => {
    jc.test('Status should be OK', () => {
        jc.response.to.have.status(200)
    })

    jc.test('Response should have user manifests', () => {
        jc.response.to.have.jsonBody('manifests')
    })

    jc.test('Response manifests should have at least 1 linux amd64 platform', () => {
        const manifests = jc.response.json('manifests')

        jc.expect(manifests).to.be.an('array').that.has.length.gte(1)

        let linuxAmd64PlatformManifest = manifests.find((m) => {
            return m.platform.os === 'linux' && m.platform.architecture === 'amd64'
        })

        jc.expect(linuxAmd64PlatformManifest).to.be.not.null
        jc.expect(linuxAmd64PlatformManifest).to.have.property('digest')

        jc.globals.set('digest', linuxAmd64PlatformManifest.digest)
    })
})

```
