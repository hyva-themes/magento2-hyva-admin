# grid > source > query > select > columns

The `<columns>` element wraps all the columns to be selected from the table that was specified with the `<from>` element.

It takes zero or more `<column>` or `<expression>` child elements.

If no `<columns>` are configured, all columns will be included in the result set (e.g. `SELECT * FROM table`).

### Example:

```html
<columns>
    <column name="status" as="order_status"/>
    <expression as="count">COUNT(*)</expression>
</columns>
```

