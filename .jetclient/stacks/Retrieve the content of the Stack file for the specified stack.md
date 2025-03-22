Get Stack file content.
**Access policy**: restricted

```toml
name = 'Retrieve the content of the Stack file for the specified stack'
description = '/stacks/{id}/file'
method = 'GET'
url = '{{baseUrl}}/stacks/{id}/file'
sortWeight = 2000000
id = '98d26080-bafc-43fd-8919-0e5ed36d51a8'

[[pathVariables]]
key = 'id'
value = '1'
description = 'Stack identifier'
```
