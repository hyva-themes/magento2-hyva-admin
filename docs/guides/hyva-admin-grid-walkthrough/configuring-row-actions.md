# Configuring Row Actions

Each row can have zero or more actions.

Actions are configured in an `<actions>` element.

```html
<actions idColumn="id">
    <action id="edit" label="Edit" url="*/*/edit"/>
    <action id="delete" label="Delete" url="*/*/delete"/>
</actions>
```

Each action is rendered as a link in a special actions column on the right side of the grid. The actions column is only present if there is at least one action.

The url format uses the Magento route syntax, where a `*` means the current route id or action path, depending on its position.

To make grid rows clickable, an action can be assigned as the default action by setting the action `id` as the `rowAction` attribute on the `columns` element:

```html
<columns rowAction="edit">
    <include>
       ...
    </include>
</columns>
```

For more information on the available attributes please head over to the [action](../../api-reference/grid-xml-reference/actions/index.md) element Grid XML API reference.
