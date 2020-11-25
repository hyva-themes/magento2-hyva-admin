<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

use Hyva\Admin\Model\HyvaGridSourceInterface;
use Magento\Backend\Model\UrlInterface as BackendUrlBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
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

    private array $navigationConfig;

    private BackendUrlBuilder $urlBuilder;

    public function __construct(
        HyvaGridSourceInterface $gridSource,
        array $navigationConfig,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        BackendUrlBuilder $urlBuilder
    ) {
        $this->gridSource            = $gridSource;
        $this->navigationConfig      = $navigationConfig;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->request               = $request;
        $this->urlBuilder            = $urlBuilder;
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
        $requestedPageNumber = (int) ($this->request->getParam('p') ?? 1);
        // Unable to limit the current page nunber to available pages
        // because it is needed to query the number of records.
        return max($requestedPageNumber, 1);
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
        $this->searchCriteriaBuilder->setCurrentPage($this->getCurrentPageNumber());

        return $this->searchCriteriaBuilder->create();
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
}
