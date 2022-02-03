# grid > source > query > select > from

The `<from>` element specifies the table to query. It is the only required child of the parent `<select>` . It has no children.

### Attributes:

The `<from>` element has a required `table` attribute an an optional `as` attribute to specify an alias for the table.

### Example:

```html
<from table="catalog_product_entity" as="main_table"/>
```

