# grid > source > query > select > join > columns

The `<columns>` element wraps all the columns to be selected from a joined table that was specified with the `<join table=”…”>` element.

It takes zero or more `<column>` or `<expression>` child elements.

(Note: the `<columns>` element nested under `<join>` works the same as the `<columns>` element that is nested under `<select>`).

If no join `<columns>` are configured, no columns will be included in the result set from the joined table (this is different from the default `<select>` columns).

### Example:

```html
<columns>
    <column name="status" as="order_status"/>
    <expression as="count">COUNT(*)</expression>
</columns>
```

