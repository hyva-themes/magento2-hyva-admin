# Filtering

By default columns are not filterable.

This is the default because filtering is the most tricky thing to get right automatically.

For simple columns types it works well, but as soon as some data is loaded in a special way it’s no longer possible to apply filters automatically.

Anyhow, filtering can be enabled for specific columns in the `<navigation><filters/></navigation>` section of a grid XML file.

```html
<navigation>
    <filters>
        <filter column="sku"/>
        <filter column="activity"/>
        <filter column="id"/>
    </filters>
</navigation>
```

The order in which filters are configured has no effect.

How a filter is rendered and how it is applied depends on the type of the column.

So for example, a datetime column filter renders as a date-range filter.

A column with string values will have a text input filter, and so on.

It’s also possible to create custom filter types if needed. Please refer to the [filters](../../api-reference/grid-xml-reference/navigation/filters/index.md) documentation in the Grid XML API reference and the [PHP Classes and Interfaces API reference](../../api-reference/php-classes-and-interfaces/index.md) for more information.

Some information on custom filters can be found in the [Filtering Extension Attribute Columns](../design-docs/filtering-extension-attribute-columns.md) document in the deep docs.
