# Upgrading to 2.0

Hyvä Admin 2.0 replaces Alpine.js 2.8 with the [Alpine.js 3 CSP build](https://alpinejs.dev/advanced/csp).
Grids no longer need `'unsafe-eval'` in the Content Security Policy of the Magento admin.

Grids that only use the grid XML configuration need no changes.
Customizations of the grid templates and custom Alpine.js code need to be updated.

## What changed

- The bundled `Hyva_Admin::js/alpine.js` is the Alpine.js 3 CSP build.
- The Alpine.js component of the grids is the static file `Hyva_Admin::js/grid.js`. It registers the component
  `initDataTable` with `Alpine.data()`. Grid specific configuration is read from data attributes on the
  `.hyva-admin-grid` element. The grid templates no longer contain inline `<script>` tags.
- The per grid functions `initDataTable_<hash>()` no longer exist. All grids use the `initDataTable` component.
- The templates reference methods of the component, for example `@click="toggleOpen"` instead of
  `@click="open = !open"`. The methods read their input from `this.$el` and `this.$event`. The methods that took
  arguments in 1.x (`isColumnVisible(key)`, `toggleColumn(key)`, `doMassAction(option)`, `formGridUpdate(form)` and
  `updateGrid(url)`) still accept them.
- Rows with a row action and buttons with a `url` use Alpine.js event listeners instead of inline `onclick`
  attributes. Buttons with an `onclick` attribute still render it as an inline event handler, which requires
  `'unsafe-inline'` in the `script-src` directive.
- The `require.js` block in the `hyva_admin_grid` layout handle has a new `grid_js_path` argument.

## Upgrade steps

### Template overrides

If you override `Hyva_Admin::grid.phtml` or any of the templates in `Hyva_Admin::grid/` in a theme or module,
start over from the new templates and apply your changes again. Templates written for 1.x don't work with 2.0.

### Custom cell, button and filter templates

The Alpine.js CSP build evaluates expressions with its own parser instead of `eval()`. Simple expressions like
`open = !open` or `updateGrid($event.target.href)` still work, but expressions can't:

- access global variables like `window`, `document` or `alert`
- assign properties of DOM objects, or call methods that change the DOM
- use `x-html`

Move such logic into your own component, registered with `Alpine.data()` in a static JavaScript file, and
reference its methods.

Templates also need to follow the [Alpine.js 3 upgrade guide](https://alpinejs.dev/upgrade-guide). For example,
`$el` is the current element in Alpine.js 3, not the component root element as in Alpine.js 2 (use `$root`),
and `@click.outside` is the recommended name for `@click.away`.

### Row action events

The [row action events](../api-reference/grid-xml-reference/actions/action/event.md) are dispatched with the same
names, and `event.detail.action`, `event.detail.params`, `event.detail.row` and `event.detail.origEvent` are
unchanged.

`event.detail.viewModel` is now the data of the Alpine.js 3 component. Code that uses Alpine.js 2 internals of the
view model needs to be updated.

### Custom Alpine.js builds

If you configure a custom `alpinejs_path` for the `require.js` block, it has to point to an Alpine.js 3 build.
Use the CSP build if the admin CSP doesn't allow `'unsafe-eval'`.

The grid component is loaded with the `grid_js_path` argument. It has to be loaded before Alpine.js, so the
component is registered when Alpine.js initializes.
