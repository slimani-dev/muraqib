List all stacks based on the current user authorizations.
Will return all stacks if using an administrator account otherwise it
will only return the list of stacks the user have access to.
Limited stacks will not be returned by this endpoint.
**Access policy**: authenticated

```toml
name = 'List stacks (1) (1)'
description = '/stacks'
method = 'GET'
url = 'http://localhost:3000/api/portainer/endpoints/1/docker/containers/json'
sortWeight = 4000000
id = 'c67cd829-7e4c-44f9-ad0a-062a9d1216ce'

[[queryParams]]
key = 'filters'
description = "Filters to process on the stack list. Encoded as JSON (a map[string]string). For example, {'SwarmID': 'jpofkc0i9uo9wtx1zesuk649w'} will only return stacks that are part of the specified Swarm cluster. Available filters: EndpointID, SwarmID."
disabled = true
```

#### Post-response Script

```js
console.log(jc.response.json().map(m =>m.Name))

```
