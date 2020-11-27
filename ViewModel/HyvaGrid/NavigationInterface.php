<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Magento\Framework\Api\SearchCriteriaInterface;

interface NavigationInterface
{
    const ASC = 'asc';
    const DESC = 'desc';

    public function getTotalRowsCount(): int;

    public function getPageCount(): int;

    public function getPageSize(): int;

    public function getUrlForPageSize(int $requestedPageSize): string;

    public function getCurrentPageNumber(): int;

    public function hasPreviousPage(): bool;

    public function getPreviousPageUrl(): string;

    public function hasNextPage(): bool;

    public function getNextPageUrl(): string;

    public function getUrlForPage(int $pageNum): string;

    public function getSearchCriteria(): SearchCriteriaInterface;

    /**
     * @return int[]
     */
    public function getPageSizes(): array;

    public function getSortByColumn(): ?string;

    public function getSortDirection(): ?string;

    public function getSortByUrl(string $columnKey, string $direction): string;
}
