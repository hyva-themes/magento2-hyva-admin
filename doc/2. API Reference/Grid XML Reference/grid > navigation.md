# grid > navigation

The grid navigation contains the paging, sorting and filtering grid configuration.

There are no attributes.


```markup
<navigation>
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


