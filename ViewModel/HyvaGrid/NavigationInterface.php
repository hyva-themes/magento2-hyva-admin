<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Magento\Framework\Api\SearchCriteriaInterface;

interface NavigationInterface
{
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
}
