# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

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
