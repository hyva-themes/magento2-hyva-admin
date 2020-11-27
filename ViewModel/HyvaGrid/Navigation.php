<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Hyva\Admin\Model\HyvaGridSourceInterface;
use Magento\Backend\Model\UrlInterface as BackendUrlBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\RequestInterface;

use function array_filter as filter;
use function array_map as map;
use function array_values as values;

class Navigation implements NavigationInterface
{
    const DEFAULT_PAGE_SIZE = 20;
    const DEFAULT_PAGE_SIZES = '10,20,50';

    private HyvaGridSourceInterface $gridSource;

    private SearchCriteriaBuilder $searchCriteriaBuilder;

    private RequestInterface $request;

    private BackendUrlBuilder $urlBuilder;

    private array $navigationConfig;

    /**
     * @var ColumnDefinitionInterface[]
     */
    private array $columnDefinitions;

    /**
     * @var SortOrderBuilder
     */
    private SortOrderBuilder $sortOrderBuilder;

    public function __construct(
        HyvaGridSourceInterface $gridSource,
        array $navigationConfig,
        array $columnDefinitions,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        RequestInterface $request,
        BackendUrlBuilder $urlBuilder
    ) {
        $this->gridSource            = $gridSource;
        $this->navigationConfig      = $navigationConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder      = $sortOrderBuilder;
        $this->request               = $request;
        $this->urlBuilder            = $urlBuilder;
        $this->columnDefinitions     = $columnDefinitions;
    }

    public function getTotalRowsCount(): int
    {
        return $this->gridSource->getTotalCount($this->getSearchCriteria());
    }

    public function getPageCount(): int
    {
        $totalRowsCount = $this->getTotalRowsCount();
        return $totalRowsCount
            ? max(1, (int) ceil($totalRowsCount / $this->getPageSize()))
            : 1;
    }

    public function getPageSize(): int
    {
        $requestedPageSize = (int) $this->request->getParam('pageSize');
        return $this->isValidPageSize($requestedPageSize)
            ? $requestedPageSize
            : $this->getDefaultPageSize();

    }

    private function getDefaultPageSize(): int
    {
        return (int) ($this->navigationConfig['pager']['defaultPageSize'] ?? self::DEFAULT_PAGE_SIZE);
    }

    public function getCurrentPageNumber(): int
    {
        $requestedPageNumber = $this->getRequestedPageNumber();
        return min(max($requestedPageNumber, 1), $this->getPageCount());
    }

    public function hasPreviousPage(): bool
    {
        return $this->getCurrentPageNumber() > 1;
    }

    public function getPreviousPageUrl(): string
    {
        $prevPage = $this->hasPreviousPage()
            ? $this->getCurrentPageNumber() - 1
            : $this->getCurrentPageNumber();

        return $this->getUrlForPage($prevPage);
    }

    public function hasNextPage(): bool
    {
        return $this->getCurrentPageNumber() < $this->getPageCount();
    }

    public function getNextPageUrl(): string
    {
        $nextPage = $this->hasNextPage()
            ? $this->getCurrentPageNumber() + 1
            : $this->getCurrentPageNumber();

        return $this->getUrlForPage($nextPage);
    }

    public function getUrlForPage(int $pageNum): string
    {
        $p = min(max($pageNum, 1), $this->getPageCount());
        return $this->urlBuilder->getUrl('*/*/*', ['_current' => true, 'p' => $p]);
    }

    public function getSearchCriteria(): SearchCriteriaInterface
    {
        $this->searchCriteriaBuilder->setPageSize($this->getPageSize());
        // The requested page number has to be used here instead of the current page number,
        // because the current page number requires the search criteria to load the records from the source,
        // which creates a circular dependency. This means the grid source has to deal with the case
        // when the page number on the search criteria is larger than the available pages.
        // However, all page links returned by this class will never go beyond the last page.
        $this->searchCriteriaBuilder->setCurrentPage($this->getRequestedPageNumber());
        $this->searchCriteriaBuilder->addSortOrder($this->createSortOrder());

        return $this->searchCriteriaBuilder->create();
    }

    private function createSortOrder(): SortOrder
    {
        $this->sortOrderBuilder->setField($this->getRequestedSortByColumn() ?? $this->getDefaultSortDirection());
        $this->getRequestedSortDirection() === self::DESC
            ? $this->sortOrderBuilder->setDescendingDirection()
            : $this->sortOrderBuilder->setAscendingDirection();
        return $this->sortOrderBuilder->create();
    }

    public function getPageSizes(): array
    {
        $pageSizeConfig = $this->navigationConfig['pager']['pageSizes'] ?? '';

        $pageSizes = $this->pageSizeConfigToArray($pageSizeConfig);

        return $pageSizes ? $pageSizes : $this->pageSizeConfigToArray(self::DEFAULT_PAGE_SIZES);
    }

    public function pageSizeConfigToArray(string $pageSizeConfig): array
    {
        return values(filter(map(function (string $s): int {
            return (int) trim($s);
        }, explode(',', $pageSizeConfig))));
    }

    public function isValidPageSize(int $pageSize): bool
    {
        return in_array($pageSize, $this->getPageSizes(), true);
    }

    public function getUrlForPageSize(int $requestedPageSize): string
    {
        $targetPageSize = $this->isValidPageSize($requestedPageSize)
            ? $requestedPageSize
            : $this->getDefaultPageSize();

        return $this->urlBuilder->getUrl('*/*/*', ['p' => 1, 'pageSize' => $targetPageSize]);
    }

    private function getRequestedPageNumber(): int
    {
        return (int) ($this->request->getParam('p') ?? 1);
    }

    public function getSortByColumn(): ?string
    {
        return $this->getRequestedSortByColumn()
            ?? $this->navigationConfig['sorting']['defaultSortByColumn']
            ?? $this->getFirstColumnKey();
    }

    private function getFirstColumnKey(): ?string
    {
        $firstColumn = values($this->columnDefinitions)[0] ?? null;
        return $firstColumn ? $firstColumn->getKey() : null;
    }

    private function getRequestedSortByColumn(): ?string
    {
        $sortBy = $this->request->getParam('sortBy');
        return isset($this->columnDefinitions[$sortBy]) ? $sortBy : null;
    }

    public function getSortDirection(): ?string
    {
        return $this->getRequestedSortDirection()
            ?? $this->navigationConfig['sorting']['defaultSortDirection']
            ?? $this->getDefaultSortDirection();
    }

    private function getDefaultSortDirection(): string
    {
        return self::ASC;
    }

    private function getRequestedSortDirection(): ?string
    {
        $direction = $this->request->getParam('sortDirection');
        return in_array($direction, [self::ASC, self::DESC], true)
            ? $direction
            : null;
    }

    public function getSortByUrl(string $columnKey, string $direction): string
    {
        return $this->urlBuilder->getUrl('*/*/*', [
            '_current'      => true,
            'p'             => 1,
            'sortBy'        => $columnKey,
            'sortDirection' => $direction,
        ]);
    }
}
