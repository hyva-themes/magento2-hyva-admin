# grid > source > query > select > join

The `<join>` element is used to configure how to join other tables into the result data set.

### Attributes

There are three `join` attributes:

* type

  The `type` attribute is used to specify the type of join. A valid value is one of:

  `inner`, `left`, `right`, `full`, `cross` and `natural`.

  If no type attribute is present, the default `left` is used.

* table (required)

  The `table` attribute specifies the name of the table to join.

* as

  The attribute `as` can be used to specify an alias for the joined table.

It has one required `<on>` child element and one optional `<columns>` child element.

### Example:

```html
<join type="left" table="catalog_product_entity_varchar" as="t_name">
    <on>t_name.entity_id=main_table.entity_id AND attribute_id=47</on>
    <columns>
        <column name="value" as="name"/>
    </columns>
</join>
```

