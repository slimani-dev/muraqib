List all stacks based on the current user authorizations.
Will return all stacks if using an administrator account otherwise it
will only return the list of stacks the user have access to.
Limited stacks will not be returned by this endpoint.
**Access policy**: authenticated

```toml
name = 'List stacks (1)'
description = '/stacks'
method = 'GET'
url = '{{baseUrl}}/stacks'
sortWeight = 3000000
id = '437a9264-5b2c-4835-948b-679a9611ec24'

[[queryParams]]
key = 'filters'
description = "Filters to process on the stack list. Encoded as JSON (a map[string]string). For example, {'SwarmID': 'jpofkc0i9uo9wtx1zesuk649w'} will only return stacks that are part of the specified Swarm cluster. Available filters: EndpointID, SwarmID."
disabled = true
```

#### Post-response Script

```js
console.log(jc.response.json().map(m =>m.Name))

```
