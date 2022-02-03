# grid > navigation > pager

The pager configuration is used to configure the default page size and the available page size dropdown.

```html
<pager enabled="true">
    <defaultPageSize>40</defaultPageSize>
    <pageSizes>20,40,100</pageSizes>
</pager>
```

When the `<pager enabled="false"/>` attribute is set, the pager will not be rendered.

The “Reset Filters” button is still shown above the grid (when filters are active), as is the column Display dropdown.