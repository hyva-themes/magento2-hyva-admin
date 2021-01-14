<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use function array_filter as filter;
use function array_keys as keys;
use function array_map as map;
use function array_merge as merge;
use function array_reduce as reduce;
use function array_values as values;
use Hyva\Admin\Model\HyvaGridSourceInterface;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface as UrlBuilder;

class Navigation implements NavigationInterface
{
    const DEFAULT_PAGE_SIZE = 20;
    const DEFAULT_PAGE_SIZES = '10,20,50';

    private HyvaGridSourceInterface $gridSource;

    private SearchCriteriaBuilder $searchCriteriaBuilder;

    private RequestInterface $request;

    private UrlBuilder $urlBuilder;

    private GridFilterInterfaceFactory $gridFilterFactory;

    private array $navigationConfig;

    /**
     * @var ColumnDefinitionInterface[]
     */
    private array $columnDefinitions;

    /**
     * @var SortOrderBuilder
     */
    private SortOrderBuilder $sortOrderBuilder;

    private string $gridName;

    /**
     * @var GridButtonInterfaceFactory
     */
    private GridButtonInterfaceFactory $gridButtonFactory;

    public function __construct(
        string $gridName,
        HyvaGridSourceInterface $gridSource,
        GridFilterInterfaceFactory $gridFilterFactory,
        array $navigationConfig,
        array $columnDefinitions,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        RequestInterface $request,
        UrlBuilder $urlBuilder,
        GridButtonInterfaceFactory $gridButtonFactory
    ) {
        $this->gridSource            = $gridSource;
        $this->navigationConfig      = $navigationConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder      = $sortOrderBuilder;
        $this->request               = $request;
        $this->urlBuilder            = $urlBuilder;
        $this->columnDefinitions     = $columnDefinitions;
        $this->gridName              = $gridName;
        $this->gridFilterFactory     = $gridFilterFactory;
        $this->gridButtonFactory     = $gridButtonFactory;
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
        $requestedPageSize = (int) $this->getQueryParam('pageSize');
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

    public function isAjaxEnabled(): bool
    {
        // When a column is rendered by a named block, ajax paging can't be used, because the layout that
        // initializes the renderer block will not be loaded during the processing of the ajax request.
        return reduce(
            $this->columnDefinitions,
            fn(bool $isEnabled, ColumnDefinitionInterface $c): bool => $isEnabled && ! $c->getRendererBlockName(),
            ($this->navigationConfig['@isAjaxEnabled'] ?? '') !== 'false'
        );
    }

    private function getNavigationRoute(): string
    {
        return $this->isAjaxEnabled()
            ? 'hyva_admin/ajax/paging'
            : '*/*/*';
    }

    public function getUrlForPage(int $pageNum): string
    {
        $p      = min(max($pageNum, 1), $this->getPageCount());
        $params = ['_current' => true, '_query' => filter(['p' => $p])];
        return $this->buildAjaxUrl($this->getNavigationRoute(), $params);
    }

    private function buildAjaxUrl(string $route, array $params): string
    {
        $nonNsQueryParams = filter([
            'ajax'     => $this->isAjaxEnabled() ? '1' : null,
            'gridName' => $this->isAjaxEnabled() ? $this->gridName : null,
        ]);
        return $this->buildUrl($route, $params, $nonNsQueryParams);
    }

    private function buildUrl(string $route, array $params, array $nonNsParams = []): string
    {
        $namespacedQuery = merge($this->qualifyQueryParamsWithGridNamespace($params['_query'] ?? []), $nonNsParams);
        return $this->urlBuilder->getUrl($route, merge($params, ['_query' => $namespacedQuery]));
    }

    private function qualifyQueryParamsWithGridNamespace(array $query): array
    {
        return reduce(keys($query), function (array $acc, string $key) use ($query): array {
            $qualifiedKey       = sprintf('%s[%s]', $this->getQueryNamespace(), $key);
            $acc[$qualifiedKey] = $query[$key];
            return $acc;
        }, []);
    }

    private function getQueryParam(string $param)
    {
        return $this->request->getParam($this->getQueryNamespace())[$param] ?? null;
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
        map(function (GridFilterInterface $filter): void {
            $filter->apply($this->searchCriteriaBuilder);
        }, filter(map([$this, 'getFilter'], keys($this->columnDefinitions))));

        return $this->searchCriteriaBuilder->create();
    }

    private function createSortOrder(): SortOrder
    {
        $key = $this->getRequestedSortByColumn() ?? $this->getDefaultSortByColumn();
        if ($key && isset($this->columnDefinitions[$key]) && $this->columnDefinitions[$key]->isSortable()) {
            $this->sortOrderBuilder->setField($key);
            $this->getRequestedSortDirection() === self::DESC
                ? $this->sortOrderBuilder->setDescendingDirection()
                : $this->sortOrderBuilder->setAscendingDirection();
        }
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

        $route = $this->getNavigationRoute();
        return $this->buildAjaxUrl($route, ['_current' => true, '_query' => ['p' => 1, 'pageSize' => $targetPageSize]]);
    }

    private function getRequestedPageNumber(): int
    {
        return (int) ($this->getQueryParam('p') ?? 1);
    }

    public function getSortByColumn(): ?string
    {
        return $this->getDefaultSortByColumn() ?? $this->getFirstColumnKey();
    }

    private function getDefaultSortByColumn(): ?string
    {
        return $this->getRequestedSortByColumn() ?? $this->navigationConfig['sorting']['defaultSortByColumn'] ?? null;
    }

    private function getFirstColumnKey(): ?string
    {
        $firstColumn = values($this->columnDefinitions)[0] ?? null;
        return $firstColumn ? $firstColumn->getKey() : null;
    }

    private function getRequestedSortByColumn(): ?string
    {
        $sortBy = $this->getQueryParam('sortBy');
        return isset($this->columnDefinitions[$sortBy]) ? $sortBy : null;
    }

    public function getSortDirection(): ?string
    {
        return $this->getRequestedSortDirection()
            ?? $this->navigationConfig['sorting']['defaultSortDirection']
            ?? $this->getDefaultSortDirection();
    }

    public function isSortOrderAscending(): bool
    {
        return $this->getSortDirection() === self::ASC;
    }

    public function isSortOrderDescending(): bool
    {
        return !$this->isSortOrderAscending();
    }

    private function getDefaultSortDirection(): string
    {
        return self::ASC;
    }

    private function getRequestedSortDirection(): ?string
    {
        $direction = $this->getQueryParam('sortDirection');
        return in_array($direction, [self::ASC, self::DESC], true)
            ? $direction
            : null;
    }

    public function getSortByUrl(string $columnKey, string $direction): string
    {
        if (!in_array($direction, [self::ASC, self::DESC], true)) {
            throw new \InvalidArgumentException('Grid Navigation sort order must be "asc" or "desc"');
        }
        return $this->buildAjaxUrl($this->getNavigationRoute(), [
            '_current' => true,
            '_query'   => [
                'p'             => 1,
                'sortBy'        => $columnKey,
                'sortDirection' => $direction,
            ],
        ]);
    }

    private function getQueryNamespace(): string
    {
        return $this->gridName;
    }

    public function hasFilters(): bool
    {
        return reduce(keys($this->columnDefinitions), function (bool $hasFilters, string $key) {
            return $hasFilters || $this->getFilterConfig($key);
        }, false);
    }

    public function getFilter(string $key): ?GridFilterInterface
    {
        $filterConfig = $this->getFilterConfig($key);

        $filter = $filterConfig && isset($this->columnDefinitions[$key])
            ? $this->gridFilterFactory->create(merge($filterConfig, [
                'gridName'         => $this->gridName,
                'filterFormId'     => $this->getFilterFormId(),
                'columnDefinition' => $this->columnDefinitions[$key],
                'request'          => $this->request,
            ]))
            : null;
        return $filter && !$filter->isDisabled()
            ? $filter
            : null;
    }

    private function getFilterConfig(string $key): ?array
    {
        return values(filter($this->navigationConfig['filters'] ?? [], function (array $filterConfig) use ($key) {
                return ($filterConfig['key'] ?? null) === $key;
            }))[0] ?? null;
    }

    public function getFilterFormUrl(): string
    {
        return $this->buildAjaxUrl($this->getNavigationRoute(), ['_current' => true]);
    }

    public function getResetFiltersUrl(): string
    {
        $route = $this->getNavigationRoute();
        return $this->buildAjaxUrl($route, ['_current' => true, '_query' => ['_filter' => '', 'p' => 1]]);
    }

    public function hasAppliedFilters(): bool
    {
        return (bool) $this->getQueryParam('_filter');
    }

    public function getFilterFormId(): string
    {
        return 'hyva-grid-filters-' . $this->gridName;
    }

    public function getButtons(): array
    {
        return map([$this, 'buildButton'], $this->sortButtonConfig($this->navigationConfig['buttons'] ?? []));
    }

    private function buildButton(array $buttonConfig): GridButtonInterface
    {
        return $this->gridButtonFactory->create(merge([], $buttonConfig));
    }

    private function sortButtonConfig(array $buttonsConfig): array
    {
        if (empty($buttonsConfig)) {
            return [];
        }
        // sort all buttons with a sortOrder before the ones without a sortOrder
        $maxSortOrder = max(map(fn(array $buttonConfig) => $buttonConfig['sortOrder'] ?? 0, $buttonsConfig)) + 1;
        usort(
            $buttonsConfig,
            fn(array $a, array $b) => ($a['sortOrder'] ?? $maxSortOrder) <=> ($b['sortOrder'] ?? $maxSortOrder)
        );

        return $buttonsConfig;
    }
}
