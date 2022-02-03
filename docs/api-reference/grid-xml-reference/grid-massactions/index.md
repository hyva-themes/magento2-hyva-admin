# grid > massActions

Mass actions allow selecting multiple records in a grid and passing their ID values to a target controller.

By default no mass actions are present in a grid.

When mass actions are configured, an additional column is rendered in leftmost position with a checkbox for each record.

Also, a dropdown with all mass actions is added above the grid. As soon as one of the options is selected, the IDs of all selected records are sent to the selected mass action controller.

If no records are selected, an alert is displayed instead of triggering the mass action.

The `massActions` element has two optional attributes:

### idColumn

The `idColumn` attribute specifies the column name that supplies the mass action ID value.

If no `idColumn` is configured, the first grid column is used to supply the record IDs.

### idsParam

The optional `idParams` attribute is used to configure the name of the query argument that is used to pass the selected ID values to the destination mass action controllers.

If no `idsParam` is specified, the `idColumn` value is used.

### Example:

```html
<massActions idColumn="id" idsParam="productIds">
    <action id="reindex" label="Reindex" url="*/massAction/reindex"/>
</massActions>
```

