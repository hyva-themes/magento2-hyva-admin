# grid > massActions > action

Each massAction action element configures an option in the mass action dropdown.

The massAction action has three required and one optional attribute:

### id

The `id` attribute is used so it is possible to reference mass actions during XML merging.

It is not used in rendering.

### label

The label is used to render the option for a mass action in the mass actions dropdown.

### url

The url is used to specify the mass action target.

The usual Magento route specification is used, i.e. `*` is used to reference the current module, action path or action. `*/*/*` refers to the current action that displays the grid. `*/*/foo` refers to the current route, current action path, `foo` action class.requireConfirmation

### requireConfirmation

By default a mass action is triggered as soon as the option is selected. When requireConfirmation="true" is set, the user is prompted after the option is selected and before the mass action is triggered.

This is useful to help avoid the accidental triggering of destructive or expensive operations.

### Example

```html
<action id="reindex" label="Reindex" url="*/massAction/reindex"/>
<action id="delete" label="Delete" url="*/massAction/delete" requireConfirmation="true"/>
```

