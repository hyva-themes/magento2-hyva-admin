# grid > navigation

The grid navigation contains the paging, sorting and filtering grid configuration.

There is one attribute, `useAjax`.

```html
<navigation useAjax="false">
    <exports>
            <export type="csv" label="Export as CSV"/>
    </exports>
    <pager>
        <pageSizes>2,5,10</pageSizes>
    </pager>
    <sorting>
        <defaultSortByColumn>foo</defaultSortByColumn>
    </sorting>
    <filters>
        <filter column="sku"/>
    </filters>
    <buttons>
        <button id="add" label="Add" url="*/*/add" enabled="false"/>
    </buttons>
</navigation>
```

By default, grids use Ajax navigation. The `useAjax` attribute can be used to disable Ajax navigation for a grid.

!!! info
    If a grid uses a column with a cell renderer via `rendererBlockName` Ajax paging will be automatically disabled for the grid, too, because the layout XML that defines the renderer block will not be loaded during Ajax navigation request processing.

    For example:

    ```html
    <column name="activity" rendererBlockName="myRendererBlock"/>
    ```
    
    A column like this will disable Ajax navigation.
