Retrieve details about a stack.
**Access policy**: restricted

```toml
name = 'Inspect a stack'
description = '/stacks/{id}'
method = 'GET'
url = '{{baseUrl}}/stacks/{id}'
sortWeight = 1000000
id = '82af5ce2-ce46-4085-8c8a-f5537e8fedb8'

[[pathVariables]]
key = 'id'
value = '1'
description = 'Stack identifier'
```
