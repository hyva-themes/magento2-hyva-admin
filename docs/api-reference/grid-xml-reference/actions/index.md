# grid > actions

By default a grid as no actions.

When actions are configured, they are rendered as the right-most column.

Currently each action is rendered as a link.

The actions element has a single optional attribute `idColumn`.

It is used to specify the column that contains the value to pass to the link destination as a query parameter.

```html
<actions idColumn="id">
    <action id="edit" label="Edit" url="*/*/edit"/>
    <action id="delete" label="Delete" url="*/*/delete"/>
</actions>
```

If no `idColumn` is configured, the first column in the grid is used to supply the record identifiers.

