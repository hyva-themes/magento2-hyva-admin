# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]
### Added
- Nothing currently.

### Changed
- Fixed example how to use `ColumnDefinition::merge()` in the docs.

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
