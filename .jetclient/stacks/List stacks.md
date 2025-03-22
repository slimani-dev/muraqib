List all stacks based on the current user authorizations.
Will return all stacks if using an administrator account otherwise it
will only return the list of stacks the user have access to.
Limited stacks will not be returned by this endpoint.
**Access policy**: authenticated

```toml
name = 'List stacks'
description = '/stacks'
method = 'GET'
url = '{{baseUrl}}/endpoints/{id}/docker/containers/'
sortWeight = 500000
id = '6b2b478f-817b-429b-9447-b2221348bc4c'

[[queryParams]]
key = 'filters'
description = "Filters to process on the stack list. Encoded as JSON (a map[string]string). For example, {'SwarmID': 'jpofkc0i9uo9wtx1zesuk649w'} will only return stacks that are part of the specified Swarm cluster. Available filters: EndpointID, SwarmID."
disabled = true

[[pathVariables]]
key = 'id'
value = '1'
```

#### Post-response Script

```js
console.log(jc.response.json().map(m =>m.Name))

```
