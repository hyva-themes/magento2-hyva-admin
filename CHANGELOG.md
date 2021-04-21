# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]
### Added
- New grid column type website_id.

### Changed
- Allow `as=""` attribute for query grid source join columns

### Removed
- No removals.

## [1.1.8] - 2021-04-17
### Added
- Nothing currently.

### Changed
- Bugfix: grids without filters throw an error that action is undefined on null.

### Removed
- No removals.

## [1.1.7] - 2021-04-11
### Added
- It's now possible to configure Exports for grids, thanks to https://github.com/pointia!

### Changed
- Nothing currently.

### Removed
- No removals.

## [1.1.6] - 2021-04-09
### Added
- A new Query Grid Source Type is now available to display DB table data without using ORM models.

### Changed
- Nothing currently.

### Removed
- No removals.

## [1.1.5] - 2021-04-06
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
### Added
- New event to customize column definitions conditionally:  
  `'hyva_grid_column_definition_build_after_' . $gridNameSuffix`  
  Thanks to [@paugnu](https://github.com/paugnu) for the PR!

### Changed
- No changes

### Removed
- No removals.

## [1.1.2] - 2021-01-28
### Added
- Filters now can have source models:
  `<filter column="store_id" source="\Magento\Config\Model\Config\Source\Store"/>`

### Changed
- No changes

### Removed
- No removals.

## [1.1.1] - 2021-01-26
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
### Added
- Add feature to declare default search criteria bindings for embedded grids.
  More information can be found in the [docs](./doc/1.%20Overview/3.%20Walkthrough/4.1%20Declaring%20source%20search%20bindings.md).

### Changed
- A number of small bugfixes that I forgot to include individually in this changelog. Still getting used to it.

### Removed
- No removals.

## [1.0.8] - 2021-01-15
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
