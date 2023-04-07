# grid > navigation > buttons

The `<buttons>` element is used to declare buttons that should be rendered above the grid.

```html
<navigation>
    <buttons>
        <button id="add" label="Add" url="*/*/add"/>
        <button id="refresh" label="Refresh Grid" onclick="window.location.reload(true)" sortOrder="1"/>
        <button id="sync" template="Module_Name::sync-button.phtml" enabled="false"/>
    </buttons>
</navigation>
```

Buttons can specify the effect they have through either an `url` or an `onclick` attribute.

They support multiple attributes:

### id (required)

The required `id` attribute is used to give a button a unique identifier. It can be used to alter a button declaration through XML merging.

### label

The `label` that will be rendered on the button. The label will be passed through the __() translation method before it is rendered.

### url

The url attribute takes a route declaration in the standard Magento notation of `routeid/action_path/action`. A `*` value indicates the current value should be used. There is no way to specify query arguments.

### onclick

With `onclick` arbitrary JavaScript can be executed when a button is clicked.

### sortOrder

By default buttons are rendered in the order they are declared. Buttons that have a `sortOrder` attribute are rendered before buttons without this attribute.

A smaller number causes a button to be rendered earlier (more to the left).

### enabled

By setting the `enabled="false"` attribute, buttons can be removed from a grid. Usually this is used through XML merging.

### template

In order to allow uttermost customizability, a custom template can be used to render a button.

If a template attribute is specified, the whole HTML for that button has to be rendered by the template .phtml file.

For example:

```html
<a class="btn btn-primary inline-flex mx-2 cursor-pointer"
    onclick="<?= $button->getOnClick() ?>">
    <span><?= $escaper->escapeHtml(__($button->getLabel())) ?></span>
</a>
```

The button instance is assigned as a template variable. It is helpful to add it to the template as a PHPDoc type hint:

```php
/** @var Hyva\Admin\ViewModel\HyvaGrid\GridButtonInterface $button */
```


---

