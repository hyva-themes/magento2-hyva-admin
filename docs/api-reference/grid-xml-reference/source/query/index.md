# grid > source > query

The query grid source configuration can be used to display the contents of a database table directly in a grid, without using intermediate PHP classes like an ORM collection or a repository.

The minimum configuration requires only a table name, but it is possible to specify the columns to select, use group by, joins and union selects, too.

Initial filters can be declared using the `defaultSearchCriteriaBindings` source configuration.

### Attributes:

There is one optional attribute:

* unionSelectType

  The `unionSelectType` attribute specifies the type of union select to use.

  If there are no `<unionSelect>` children the attribute has no effect.

  A valid value is one of `all` or `distinct`. If no union select type is configured, `all` will be used by default.

The `<query>` element must have one `<select>` child element and can have zero or more

`<unionSelect>` element children.

### Example:

```html
<?xml version="1.0"?>
<grid xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:noNamespaceSchemaLocation="urn:magento:module:Hyva_Admin:etc/hyva-grid.xsd">
    <source>
        <query unionSelectType="all">
            <select>
                <from table="sales_order"/>
                <columns>
                    <column name="status" as="order_status"/>
                    <column name="state" as="order_state"/>
                    <column name="created_at" as="latest_order"/>
                    <expression as="count">COUNT(*)</expression>
                </columns>
                <groupBy>
                    <column name="status"/>
                    <column name="state"/>
                </groupBy>
            </select>
            <unionSelect>
                <from table="some_other_table_with_4_columns">
            </unionSelect>
        </query>
    </source>
</grid>
```

