# Configuring Mass Actions

Mass actions allow users to select multiple records on a grid and submit all selected IDs to a controller at once.

The mass action can be selected with a select input above the grid.

```html
<massActions idColumn="id">
    <action id="reindex" label="Reindex" url="*/massAction/reindex"/>
    <action id="delete" label="Delete" url="*/massAction/delete" requireConfirmation="true"/>
</massActions>
```

When one or more mass actions are configured, a column with a checkbox is rendered on the left side of the grid for each row.

For more details on mass actions and available attributes, please check out the [massActions](../../api-reference/grid-xml-reference/massactions/index.md) Grid XML api reference.
