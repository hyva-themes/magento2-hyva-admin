# grid > columns > include

If present, only the included columns will be rendered. The behavior can be changed by specifying the attribute `keepAllSourceColumns="true"`.

## Examples:

Display only the `id` and `sku` columns:

```html
<include>
    <column name="id"/>
    <column name="sku"/>
</include>
```

Display all available columns:

```html
<include keepAllSourceColumns="true">
    <column name="id"/>
    <column name="sku"/>
</include>
```

Using `keepAllSourceColumns="true"` is useful for specifying additional properties for individual columns, or for moving only some columns to the left side of the grid.