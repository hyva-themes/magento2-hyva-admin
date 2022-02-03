# grid > source > query > select > columns > column

The select `<column>` element is used to add a column of the select table to the query result set.

### Attributes:

There is one required and one optional attribute:

* name (required)

  The `name` attribute specifies the column name to include in the result.
* as

  The `as` attribute specifies the alias to use for the column.

### Example:

```html
<column name="entity_id" as="id"/>
```

