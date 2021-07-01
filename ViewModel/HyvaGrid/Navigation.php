<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use function array_column as pick;
use function array_combine as zip;
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
use Magento\Framework\HTTP\PhpEnvironment\Request as HttpRequest;
use Magento\Framework\UrlInterface as UrlBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

class Navigation implements NavigationInterface
{
    public const DEFAULT_PAGE_SIZE = 20;
    public const DEFAULT_PAGE_SIZES = '10,20,50';

    /**
     * @var HyvaGridSourceInterface
     */
    private $gridSource;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SearchCriteriaInterface
     */
    private $memoizedSearchCriteria;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * @var GridFilterInterfaceFactory
     */
    private $gridFilterFactory;

    /**
     * @var array[]
     */
    private $navigationConfig;

    /**
     * @var ColumnDefinitionInterface[]
     */
    private $columnDefinitions;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var string
     */
    private $gridName;

    /**
     * @var GridButtonInterfaceFactory
     */
    private $gridButtonFactory;

    /**
     * @var GridExportInterfaceFactory
     */
    private $gridExportFactory;

    /**
     * @var LayoutInterface
     */
    private $layout;

    public function __construct(
        string $gridName,
        LayoutInterface $layout,
        HyvaGridSourceInterface $gridSource,
        GridFilterInterfaceFactory $gridFilterFactory,
        array $navigationConfig,
        array $columnDefinitions,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        RequestInterface $request,
        UrlBuilder $urlBuilder,
        GridButtonInterfaceFactory $gridButtonFactory,
        GridExportInterfaceFactory $gridExportFactory
    ) {
        $this->layout                = $layout;
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
        $this->gridExportFactory     = $gridExportFactory;
    }

    public function getTotalRowsCount(): int
    {
        return $this->gridSource->getTotalCount($this->getSearchCriteria());
    }

    public function isPagerEnabled(): bool
    {
        return ($this->navigationConfig['pager']['@enabled'] ?? '') !== 'false';
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

    public function getFirstPageUrl(): string
    {
        return $this->getUrlForPage(1);
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
            function (bool $isEnabled, ColumnDefinitionInterface $c): bool {
                return $isEnabled && !$c->getRendererBlockName();
            },
            ($this->navigationConfig['@isAjaxEnabled'] ?? '') !== 'false'
        );
    }

    private function getNavigationRoute(): string
    {
        return $this->isAjaxEnabled()
            ? 'hyva_admin/ajax/paging'
            : '*/*/*';
    }

    public function getLastPageUrl(): string
    {
        return $this->getUrlForPage($this->getPageCount());
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
            'origRoute' => $this->getCurrentRoute($this->request),
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
        // refactor: get rid of this memoization
        if (!isset($this->memoizedSearchCriteria)) {
            if ($this->isPagerEnabled()) {
                $this->searchCriteriaBuilder->setPageSize($this->getPageSize());
                // The requested page number has to be used here instead of the current page number,
                // because the current page number requires the search criteria to load the records from the source,
                // which creates a circular dependency. This means the grid source has to deal with the case
                // when the page number on the search criteria is larger than the available pages.
                // However, all page links returned by this class will never go beyond the last page.
                $this->searchCriteriaBuilder->setCurrentPage($this->getRequestedPageNumber());
            }
            $this->searchCriteriaBuilder->addSortOrder($this->createSortOrder());
            map(function (GridFilterInterface $filter): void {
                $filter->apply($this->searchCriteriaBuilder);
            }, filter(map([$this, 'getFilter'], keys($this->columnDefinitions))));

            $this->memoizedSearchCriteria = $this->searchCriteriaBuilder->create();
        }
        return $this->memoizedSearchCriteria;
    }

    private function createSortOrder(): SortOrder
    {
        $key = $this->getRequestedSortByColumn() ?? $this->getDefaultSortByColumn();
        if ($key && isset($this->columnDefinitions[$key]) && $this->columnDefinitions[$key]->isSortable()) {
            $this->sortOrderBuilder->setField($key);
            ($this->getRequestedSortDirection() ?? $this->getDefaultSortDirection()) === self::DESC
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
        return $this->getRequestedSortByColumn() ?? $this->getDefaultSortByColumn() ?? $this->getFirstColumnKey();
    }

    private function getDefaultSortByColumn(): ?string
    {
        return $this->navigationConfig['sorting']['defaultSortByColumn'] ?? null;
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
        return $this->navigationConfig['sorting']['defaultSortDirection'] ?? self::ASC;
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
        return map([$this, 'buildButton'], $this->sortElements($this->navigationConfig['buttons'] ?? []));
    }

    private function buildButton(array $buttonConfig): GridButtonInterface
    {
        return $this->gridButtonFactory->create(merge([], $buttonConfig));
    }

    private function sortElements(array $elementsConfig): array
    {
        // Sort elements with a sortOrder before the ones without a sortOrder.
        // At the time of writing elements are buttons ands exports.
        $maxSortOrder = empty($elementsConfig)
            ? 0
            : max(map(function (array $buttonConfig) {
                return $buttonConfig['sortOrder'] ?? 0;
            }, $elementsConfig)) + 1;
        usort(
            $elementsConfig,
            function (array $a, array $b) use ($maxSortOrder) {
                return ($a['sortOrder'] ?? $maxSortOrder) <=> ($b['sortOrder'] ?? $maxSortOrder);
            }
        );

        return $elementsConfig;
    }

    /**
     * @return GridExportInterface[]
     */
    public function getExports(): array
    {
        $exportsConfig = filter($this->navigationConfig['exports'] ?? [], function (array $exportConfig): bool {
            return ($exportConfig['enabled'] ?? 'true') !== 'false';
        });
        $sortedConfigs = $this->sortElements($exportsConfig);
        $ids           = pick($sortedConfigs, 'type');

        return zip($ids, map([$this->gridExportFactory, 'create'], $sortedConfigs));
    }

    public function getExportUrl(string $type): string
    {
        return $this->buildUrl('hyva_admin/export/download', [
            // preserve filters and order for export, page and pageSize will be ignored
            '_current'   => true,
            'gridName'   => $this->gridName,
            'exportType' => $type,
        ]);
    }

    public function getHtml(): string
    {
        $renderer = $this->layout->createBlock(Template::class);
        $renderer->setTemplate('Hyva_Admin::grid/navigation.phtml');
        $renderer->assign('navigation', $this);

        return $renderer->toHtml();
    }

    private function getCurrentRoute(RequestInterface $request): string
    {
        return $request instanceof HttpRequest
            ? $this->buildCurrentRoute($request)
            : '';

    }

    private function buildCurrentRoute(HttpRequest $request): string
    {
        return $request->getRouteName() . '/' . $request->getControllerName() . '/' . $request->getActionName();
    }
}
