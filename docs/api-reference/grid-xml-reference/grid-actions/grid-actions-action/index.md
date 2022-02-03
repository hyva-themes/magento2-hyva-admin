# grid > actions > action

Each action is rendered as a link.

An action element has three required attributes and one optional one:

### id

The id attribute is used so it is possible to reference actions during XML merging or as a row action.

It is not used in rendering.

### label

The label is used to render the link for that action in the action column.

### url

The url is used to specify the action target.

The usual Magento route specification is used, i.e. `*` is used to reference the current module, action path or action. `*/*/*` refers to the current action that displays the grid. `*/*/foo` refers to the current route, current action path, `foo` action class.

### idParam (optional)

The `idParam` attribute is used to configure the query parameter name to pass the id column value to the target action. If no `idParam` is present, the `idColumn` name is used.

If no `idColumn` is set on the actions element, the `idColumn` defaults to the `idParam`, or - if that doesn’t match a column - the first column in the grid.

### Examples:

```html
<actions idColumn="id">
    <action id="edit" label="Edit" url="*/*/edit"/>
    <action id="delete" label="Delete" url="*/*/delete"/>
</actions>
```

Render two actions **Edit** and **Delete**. Both will pass the value of the column `id` with the query param `id` to the destination controller.

```html
<actions>
    <action id="edit" label="Edit" url="*/*/edit"/>
</actions>
```

Render one action **Edit**. Pass the value of the first column to the destination controller using the query param named after the first column key.

```html
<actions>
    <action id="delete" label="Delete" url="*/*/delete" idParam="sku"/>
</actions>
```

Render one action **Delete**. If there is a column `sku` in the grid, pass the value of that column to the destination controller with the query param `sku`.

If there is *no column* `sku`, pass the value of the first column to the destination controller using the query param `sku`.

```html
<actions idColumn="sku">
    <action id="delete" label="Delete" url="*/*/delete"/>
</actions>
```

Render one action **Delete**. Pass the value of the column `sku` to the destination controller using the query parameter `sku`.
*Note: you will have to create the target actions yourself - they are not created automatically by Hyva_Admin ;)*