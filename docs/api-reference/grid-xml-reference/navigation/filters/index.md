# grid > navigation > filters

The `filters` configuration is used to specify which columns are filterable.

There are a number of different filter types, and custom filters can also be used.

```html
<filters>
    <filter column="id"/>
    <filter column="sku"/>
    <filter column="activity"/>
</filters>
```

By default no filters are rendered.

The filter type is determined automatically based on a columns type.

More information on filter types can be found on the PHP class documentation for `Hyva\Admin\Api\GridFilterTypeInterface`.

Not all column types can automatically be mapped to a filter type. In such cases a `filterType` class needs to be set on a `filter`.
