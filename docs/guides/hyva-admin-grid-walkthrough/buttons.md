# Buttons

Buttons that should be rendered above the grid can be declared as part of the navigation:

```html
<navigation>
    <buttons>
        <button id="add" label="Add" url="*/*/add"/>
        <button id="refresh" label="Refresh Page" onclick="window.location.reload(true)" sortOrder="-1"/>
    </buttons>
</navigation>
```

Buttons can use either an `url` or an `onclick` attribute to specify their effect.

Please refer to the Grid XML API Reference for more information.
