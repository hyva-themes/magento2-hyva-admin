# grid > source > query > unionSelect

A query can have zero or more `unionSelect` children.

The unionSelect element has no attributes.

It can contain all the configuration from the `<select>` attribute.

The specified select will be combined with the primary select configuration in a union select.

The type of the union select (`UNION DISTINCT` or `UNION ALL`) can be set with the `unionSelectType` attribute on the `<query>` element. If no unionSelectType is configured, the default `UNION ALL` is used.

Please refer to the [grid > source > query > select](select/index.md) documentation for more information on the allowed children elements.
