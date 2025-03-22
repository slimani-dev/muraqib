```toml
name = 'manifest (1)'
method = 'GET'
url = 'https://lscr.io/v2/linuxserver/bazarr/manifests/sha256:68c3aeb8e08d53b50e43ffcbc3ab5e7d8ecec8e27dce422976477bc585e3f74f'
sortWeight = 1500000
id = 'faff3cea-f9e2-4cad-922d-74cf830ce580'

[[pathVariables]]
key = 'registry'
value = 'registry-1.docker.io'

[[pathVariables]]
key = 'imageName'
value = 'portainer/portainer-ce'

[[pathVariables]]
key = 'tag'
value = 'latest'

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
