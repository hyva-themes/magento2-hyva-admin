# Default Sort Order

The default sort order can be configured in the `<navigation><sorting/></navigation>` part of a grid XML file.

```html
<sorting>
    <defaultSortByColumn>created_at</defaultSortByColumn>
    <defaultSortDirection>desc</defaultSortDirection>
</sorting>
```

Users can change the sort order by clicking on the column titles. Clicking on a title multiple times reverses the sort order.

For more detailed information about which columns are sortable and how sorting works, please refer to the [Sorting Columns](../design-docs/sorting-columns.md) documentation in the design docs.
