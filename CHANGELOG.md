# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

[Unreleased]: https://github.com/hyva-themes/magento2-hyva-admin/compare/1.1.22..main

### Added
- Nothing

### Changed
- Nothing

### Removed
- No removals.

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
