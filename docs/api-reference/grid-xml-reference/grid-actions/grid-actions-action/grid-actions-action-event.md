# grid > actions > action > event

**Status: experimental**

**The API for the action event node might be removed or changed in future.**

Specifying event triggers on actions allows creating complex ui customizations.

Only the event trigger can be specified in the grid XML:

```html
<actions>
    <action id="delete" label="Delete" url="*/*/delete">
        <event on="click"/>
    </action>
</actions>
```

## Event Name

The event name is built based on the grid name, the action id and the event trigger:

```php
private function getEventName(): string
{
    $gridNameInEvent = $this->eventify($this->gridName);
    return sprintf('hyva-grid-%s-action-%s-%s', $gridNameInEvent, $this->eventify($this->targetId), $this->on);
}
```

For example, given a grid named products-query-grid, and an action with the id delete, the JavaScript event that is dispatched is

`hyva-grid-products-query-grid-action-delete-click`

## Event Subscribers

Event subscribers can be declared in .phtml template files that are added to the grid page via layout XML.

Example:

```js
<script>
window.addEventListener('hyva-grid-products-grid-action-delete-click', e => {
    if (! confirm('<?= __('Are you sure?') ?>')) {
        e.detail.origEvent.preventDefault();
    }
});
</script>
```

## Event Arguments

The event arguments can be retrieved from the events.detail property in subscribers.

### event.detail.origEvent

This is the original event that was triggered by the user interaction.

probably this is mainly useful to abort user actions with `event.detail.origEvent.preventDefault()`.

### event.detail.row

This property is a reference to the clicked grid table row.

It might be useful to retrieve the rendered cell values in a kind of hacky way.

### event.detail.viewModel

This is the Alpine.js view model of the grid.

### event.detail.action

This is the grid action id. In the examples on this page it is the string `delete`.

### event.detail.params

This is the map of parameters that would be passed to the URL. It depends on the action configuration.

The following example will add the params `{foo => idValue}`:

```html
<actions idColumn="id">
    <action id="delete" label="Delete" url="*/*/delete" idParam="foo">
        <event on="click"/>
    </action>
</actions>
```
