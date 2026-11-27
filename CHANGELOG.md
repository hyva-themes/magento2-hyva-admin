# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [2.0.0] - 2026-11-27
[2.0.0]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.29..2.0.0

This release is not backward compatible with templates and JavaScript written for Hyvä Admin 1.x.
See the [upgrade guide](https://hyva-themes.github.io/magento2-hyva-admin/guides/upgrading-to-2.0/).
Hyvä Admin 1.x is still maintained in the `main-1.x` branch, so you can stay on `^1.1` until you're ready to upgrade.

### Changed
- Alpine.js 2.8 is replaced with the Alpine.js 3.17.5 CSP build. Grids no longer require `'unsafe-eval'` in the
  admin Content Security Policy.
- The grid Alpine.js component is a static JavaScript file (`Hyva_Admin::js/grid.js`), registered as
  `Alpine.data('initDataTable')` and configured with data attributes on the grid element. The grid templates no longer
  render inline scripts.
- Row actions and buttons with an `url` use Alpine.js event listeners instead of inline `onclick` attributes.
  Buttons with an `onclick` attribute still need `'unsafe-inline'`.
- Row action events: `event.detail.viewModel` is the Alpine.js 3 component. An action can declare events for several
  event types.
- Grid output is escaped in more places, for example the filter form ids and the filter option labels.
- Fixed: "select all" only selects the rows of its own grid when there are several grids on a page.

### Upgrade notes
- Theme or module overrides of `Hyva_Admin::grid.phtml` and the `Hyva_Admin::grid/*` templates need to be redone
  based on the new templates.
- Custom cell, button and filter templates have to follow the Alpine.js 3 syntax and the restrictions of the CSP
  build: expressions can't access globals like `window` or `document`, change the DOM, or use `x-html`. Move such
  logic into a component registered with `Alpine.data()`.
- A custom `alpinejs_path` has to point to an Alpine.js 3 build.
- Clear the Magento cache after the upgrade, so the updated layout loads `Hyva_Admin::js/grid.js`.

Thanks to [@pykettk](https://github.com/hyva-themes/magento2-hyva-admin/pull/93) for the initial implementation!

## [1.1.29] - 2026-11-27
[1.1.29]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.28..1.1.29

### Security
- Stored XSS in grid row actions with a JS event (`<action><event on="click"/></action>`): the action parameters
  contain the row value of the `idColumn` declared on `<actions>`, and were rendered unescaped into the `x-on:`
  attribute of the action link. A value containing a single quote could add attributes (like event handlers) to the
  link, and a value containing an HTML entity like `&quot;` could inject JavaScript into the event expression.
  The parameters are now JSON encoded with quotes, ampersands and angle brackets as unicode escapes, and the template
  escapes the parameters and the event name. Grids where the `idColumn` holds a numeric ID were not affected.
  If you override the `Hyva_Admin::grid.phtml` template, apply the same change to the `x-on:` attribute of the
  action links.  
  Thanks to [Ief Falot](https://github.com/iefken) for the report!  
  Advisory: [GHSA-h358-xv36-2j2f](https://github.com/hyva-themes/magento2-hyva-admin/security/advisories/GHSA-h358-xv36-2j2f)
- Stored XSS in product image cells: the `magento_product_image` and `gallery` column types and media gallery entries
  rendered the image URL unescaped into the `src` attribute of the `<img>` element when the column is configured with
  `renderAsUnsecureHtml="true"`, as the docs recommend. The URL contains the image path from the product data, so a
  path containing a double quote could add attributes (like event handlers) to the element.
  `ProductMediaInBackend::getImageHtmlElement()` now escapes the image URL. The `toHtmlRecursive()` example in the
  docs escapes the image URL, too.  
  Advisory: [GHSA-mf9x-p67h-j4gv](https://github.com/hyva-themes/magento2-hyva-admin/security/advisories/GHSA-mf9x-p67h-j4gv)
- The admin ajax paging (`hyva-admin/ajax/paging`) and export (`hyva-admin/export/download`) endpoints served any
  grid to any admin user, regardless of the ACL resource that protects the page the grid is rendered on.
  They now only serve grids that were rendered for the admin user during their current admin session.
  Only grids rendered with a Hyvä grid block (any block extending `Hyva\Admin\Block\BaseHyvaGrid`) are recorded.
  Grids are no longer rendered through template directives like `{{block}}` or `{{layout}}`, in any area (this
  includes storefront CMS content and emails).  
  Thanks to Sansec and [Ief Falot](https://github.com/iefken) for the reports!  
  Advisory: [GHSA-98w6-75x7-mccj](https://github.com/hyva-themes/magento2-hyva-admin/security/advisories/GHSA-98w6-75x7-mccj)
- The storefront route `hyva-grid` was removed. Its export endpoint served grids declared in `view/base/hyva-grid`
  to visitors without authentication.
- The ajax paging endpoint no longer returns exception messages outside of developer mode. They are logged instead.
- CSV exports prefix cells that start with `=`, `+`, `-`, `@`, a tab or a carriage return with a single quote, so
  spreadsheet apps don't evaluate them as formulas (CSV injection). Numbers like `-5` are not prefixed.
  Before, Magento only prefixed values starting with `=`, `+` or `-` with a space.
  XLSX and XML exports store the values as text and are not affected.
- Docs: `renderAsUnsecureHtml` must only be used for trusted values or data types that build safe HTML, and
  `toHtmlRecursive()` implementations of custom data types have to escape the values they embed.

### Added
- Grids can declare the ACL resource an admin user needs to see the grid with the `acl` attribute:
  `<grid acl="My_Module::my_grid">`. Grids the admin user isn't allowed to access are not rendered, and are not
  served by the ajax paging and export endpoints.

### Changed
- Fixed: Grids were not interactive since 1.1.26 (no ajax paging, sorting, filtering, mass actions or column toggle).
  The Alpine.js script was deferred, so it ran after RequireJS and registered itself as an anonymous AMD module
  instead of initializing ("Mismatched anonymous define() module"). Alpine.js is loaded without `defer` again.
- PHP 8.5 compatibility: avoid using null as an array offset, which is deprecated in PHP 8.5 and made every grid
  without a sortBy query parameter or row action throw an exception in developer mode.
- Fixed: Type reflection of classes with union or intersection types (PHP 8.0+) failed with
  "Call to undefined method ReflectionUnionType::getName()". These types are now reduced to their first non-null type.
- Tests: Data providers are declared with PHPUnit attributes in addition to annotations and are static, so the test
  suites run on PHPUnit 10 and newer.
- Fixed: Filters are ignored on grids with an array provider source (issue #62).
  Grid source processors for array provider grids are now called on every data fetch with the search criteria of that fetch,
  and `afterLoad` receives the filtered records. The provider data itself is still loaded only once per request.
- Fixed: Grids with an array provider and included columns threw "Column(s) not found on source" when the provider
  returned no data (issue #85). The included columns are now rendered without records.  
  Thanks to [@pykettk](https://github.com/hyva-themes/magento2-hyva-admin/pull/89) for the PR!
- Docs: Grid source processors can be called multiple times per request (for every search criteria and, for array
  grids, for the column detection) and need to be idempotent.

### Removed
- The storefront route `hyva-grid` with the controllers `Hyva\Admin\Controller\Ajax\Paging` and
  `Hyva\Admin\Controller\Export\Download`. Outside of the admin, grids are now rendered without ajax paging and
  without export links. Custom storefront grid templates that build these URLs themselves need to be updated.

## [1.1.23] - 2025-02-06
[1.1.23]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.22..1.1.23
### Added

- Nothing

### Changed

- Fixed: Boolean filter behavior in grids with multiple filters (thanks to Andreas von Studnitz)

### Removed

- Nothing

## [1.1.22] - 2023-12-08
[1.1.22]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.21..1.1.22
### Added

- Nothing

### Changed

- Fixed: Ajax Grid: after filtering all mass actions redirect to admin dashboard 

### Removed

- Nothing


## [1.1.18] - 2022-04-12
[1.1.18]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.17..1.1.18
### Added

- Nothing

### Changed

- PHP 8.1 compatibility changes. All changes are backward compatible.

- Dropping PHP 7.3 support

### Removed

- Nothing

## [1.1.17] - 2022-03-08
[1.1.17]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.16..1.1.17
### Added
- French Translation  
  Thanks to Frederic Martinez for the [PR](https://github.com/hyva-themes/magento2-hyva-admin/pull/64)!

### Changed
- Grid Labels are now translated  
  Thanks to Frederic Martinez for the [PR](https://github.com/hyva-themes/magento2-hyva-admin/pull/63)!

### Removed
 - Nothing

## [1.1.16] - 2021-07-01
[1.1.16]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.15..1.1.16
### Added
- Support grid column data types `smallint` and `mediumint`

### Changed
- Bugfix: modules enabled in app/etc/config.php but not installed no longer throw an exception.  
  Thanks to [Mirko Cesaro](https://github.com/hyva-themes/magento2-hyva-admin/issues/53) for reporting!
  
- Bugfix: grid sources with an accessor methods starting with `is` (e.g. `isActive`) no longer always 
  return `null` if the object also implements magic getters.
  
- Bugfix: for ajax grids, grid action URLs with `*` as part of the new path will now resolve to the original route instead of using the ajax route.
  This closes the issue #56.
  
- Use TailwindCSS forms plugin strategy `class`. This will improve compatibility with
  existing Magento forms. Some modules that relied on the automatic form styles resets might need to be updated.
  More information can be found at https://github.com/tailwindlabs/tailwindcss-forms#using-classes-instead-of-element-selectors

### Removed
- No removals.

## [1.1.15] - 2021-05-26
[1.1.15]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.14..1.1.15
### Added
- Nothing

### Changed
- Removed Umlaut from file name in documentation, because it breaks composer package building.  
  Thanks to [Pieter Hoste](https://github.com/hyva-themes/magento2-hyva-admin/issues/50) for letting me know.

### Removed
- Nothing

## [1.1.14] - 2021-05-14
[1.1.14]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.13..1.1.14
### Added
- New collection grid source type specific processor interface
  `HyvaGridCollectionProcessorInterface` extending `HyvaGridSourceProcessorInterface`

  If processors implement this interface, an `afterInitSelect` method is called for collections.
  This allows joining fields before the available grid columns are extracted or the search criteria is applied.

- New method `getGridName` on all grid source types.
  This might be handy in plugins.

## [1.1.13] - 2021-05-5
[1.1.13]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.12..1.1.13
No changes, this is a fix for a botched 1.1.12 release.

## [1.1.12] - 2021-05-5
[1.1.12]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.11..1.1.12
### Added
- New experimental JavaScript events implementation for grid actions.

### Changed
- Nothing.

### Removed
- No removals.

## [1.1.11] - 2021-04-27
[1.1.11]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.10..1.1.11
### Added
- Added $escaper declaration to templates for Magento 2.3 compatibility.

### Changed
- Nothing.

### Removed
- No removals.

## [1.1.10] - 2021-04-23
[1.1.10]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.9..1.1.10
### Added
- Nothing yet.

### Changed
- Use `in_array` instead of external dependency that declares `array_contains`.
  Thanks to Helge Baier who made me aware of this bug.

### Removed
- No removals.

## [1.1.9] - 2021-04-23
[1.1.9]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.8..1.1.9
### Added
- New grid column type website_id.
- New low level grid customization technique via \Hyva\Admin\Api\HyvaGridSourceProcessorInterface
  They can be configured on grids in the XML at grid/source/processors.

### Changed
- Allow `as=""` attribute for query grid source join columns
- Improved grid styling a bit (I hope)

### Removed
- No removals.

## [1.1.8] - 2021-04-17
[1.1.8]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.7..1.1.8
### Added
- Nothing currently.

### Changed
- Bugfix: grids without filters throw an error that action is undefined on null.

### Removed
- No removals.

## [1.1.7] - 2021-04-11
[1.1.7]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.6..1.1.7
### Added
- It's now possible to configure Exports for grids, thanks to https://github.com/pointia!

### Changed
- Nothing currently.

### Removed
- No removals.

## [1.1.6] - 2021-04-09
[1.1.6]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.5..1.1.6
### Added
- A new Query Grid Source Type is now available to display DB table data without using ORM models.

### Changed
- Nothing currently.

### Removed
- No removals.

## [1.1.5] - 2021-04-06
[1.1.5]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.4..1.1.5
### Added
- Add composer dependency on laminas/laminas-code:^3.0.0 for Magento 2.3 compatibility.
- Read return type from method declaration on interfaces if present.
- Use Select inspection on collection grid source type for columns that do not have getters or setters.
  This also supports fields added with joins and custom fields on existing flat tables.

### Changed
- Nothing currently.

### Removed
- No removals.


## [1.1.4] - 2021-03-15
[1.1.4]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.3..1.1.4
### Added
- Add polyfill for `\Magento\Framework\View\Helper\SecureHtmlRenderer` for
  `view/adminhtml/templates/page/js/require_js.phtml` Magento 2.3 compatibility.

### Changed
- Bugfix: allow underscore in route-id for route declarations in XML
- Bugfix: grid column filters with a zero value are now possible
- Bugfix issue #42: array grid providers returning an empty array no longer produce a stack trace.
- Fixed example how to use `ColumnDefinition::merge()` in the docs.
- Bugfix issue #33: With active filters the array grid source type page count still is calculated based on all grid
  records, not the filtered grid entries, resulting in added blank pages at the end.
  
### Removed
- No removals.

## [1.1.3] - 2021-02-05
[1.1.3]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.2..1.1.3
### Added
- New event to customize column definitions conditionally:  
  `'hyva_grid_column_definition_build_after_' . $gridNameSuffix`  
  Thanks to [@paugnu](https://github.com/paugnu) for the PR!

### Changed
- No changes

### Removed
- No removals.

## [1.1.2] - 2021-01-28
[1.1.2]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.1..1.1.2
### Added
- Filters now can have source models:
  `<filter column="store_id" source="\Magento\Config\Model\Config\Source\Store"/>`

### Changed
- No changes

### Removed
- No removals.

## [1.1.1] - 2021-01-26
[1.1.1]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.0..1.1.1
### Added
- Support collections that use the generic entity type
  `\Magento\Framework\View\Element\UiComponent\DataProvider\Document`
  This is mainly used in the standard adminhtml (non-hyva) grids.
  For example `Magento\Sales\Model\ResourceModel\Order\Grid\Collection´

### Changed
- Backward compatible bugfix for select filter with ajax pagination.

### Removed
- No removals.

## [1.1.0] - 2021-01-19
[1.1.0]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.0.8..1.1.0
### Added
- Add feature to declare default search criteria bindings for embedded grids.
  More information can be found in the [docs](./doc/1.%20Overview/3.%20Walkthrough/4.1%20Declaring%20source%20search%20bindings.md).

### Changed
- A number of small bugfixes that I forgot to include individually in this changelog. Still getting used to it.

### Removed
- No removals.

## [1.0.8] - 2021-01-15
[1.0.8]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.0.7..1.0.8
### Added
- Add this CHANGELOG.md file.
- New column attribute `initiallyHidden`. When set to `true`, a column will be rendered
  in HTML but hidden by JavaScript on the initial page load. The Display dropdown can be used to show the column
  when needed.
  Thanks to [@paugnu](https://github.com/hyva-themes/magento2-hyva-admin/pull/22) for this feature.
- Buttons to navigate to the first and last page are now part of the pagination.
  Thanks to [@Bartlomiejsz](https://github.com/hyva-themes/magento2-hyva-admin/pull/23) for this feature.
- New `pager` attribute `enabled`. When set to `false` no pagination is rendered, and no current page
  and page size is set on the search criteria passed to the grid data providers.
- Ajax Paging. Ajax paging is the default navigation mode. It can be disabled by setting the `useAjax` attribute
  on the `pager` element to `false`. `<pager useAjax="false"/>` .

### Changed
- Changed the column type `long_text` to function as a non-truncating text type.
  The default behavior remains that text content is truncated if it's longer than 30 characters.

### Removed
- No removals.
