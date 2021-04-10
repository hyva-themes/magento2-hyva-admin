<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Magento\Framework\Api\SearchCriteriaInterface;

interface NavigationInterface
{
    public const ASC = 'asc';
    public const DESC = 'desc';

    public function getTotalRowsCount(): int;

    public function isPagerEnabled(): bool;

    public function getPageCount(): int;

    public function getPageSize(): int;

    public function getUrlForPageSize(int $requestedPageSize): string;

    public function getCurrentPageNumber(): int;

    public function hasPreviousPage(): bool;

    public function getPreviousPageUrl(): string;

    public function getFirstPageUrl(): string;

    public function hasNextPage(): bool;

    public function getNextPageUrl(): string;

    public function getLastPageUrl(): string;

    public function getUrlForPage(int $pageNum): string;

    public function isAjaxEnabled(): bool;

    public function getSearchCriteria(): SearchCriteriaInterface;

    /**
     * @return int[]
     */
    public function getPageSizes(): array;

    public function getSortByColumn(): ?string;

    public function getSortDirection(): ?string;

    public function isSortOrderAscending(): bool;

    public function isSortOrderDescending(): bool;

    public function getSortByUrl(string $columnKey, string $direction): string;

    public function hasFilters(): bool;

    public function getFilter(string $key): ?GridFilterInterface;

    public function getFilterFormUrl(): string;

    public function getResetFiltersUrl(): string;

    public function hasAppliedFilters(): bool;

    public function getFilterFormId(): string;

    /**
     * @return GridButtonInterface[]
     */
    public function getButtons(): array;

    /**
     * @return GridExportInterface[]
     */
    public function getExports(): array;

    public function getExportUrl(string $type): string;

    public function getHtml(): string;
}
