<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use Hyva\Admin\Api\DataTypeGuesserInterface;
use Hyva\Admin\Model\GridSourceType\Internal\RawGridSourceDataAccessor;
use Hyva\Admin\Model\RawGridSourceContainer;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;

use function array_keys as keys;
use function array_reduce as reduce;
use function array_slice as slice;
use function array_values as values;

class ArrayProviderGridSourceType implements GridSourceTypeInterface
{
    private RawGridSourceDataAccessor $gridSourceDataAccessor;

    private ArrayProviderSourceType\ArrayProviderFactory $arrayProviderFactory;

    private ColumnDefinitionInterfaceFactory $columnDefinitionFactory;

    private DataTypeGuesserInterface $dataTypeGuesser;

    private array $memoizedGridData;

    private SearchCriteriaBuilder $searchCriteriaBuilder;

    private string $arrayProviderClass;

    private string $gridName;

    public function __construct(
        string $gridName,
        array $sourceConfiguration,
        RawGridSourceDataAccessor $gridSourceDataAccessor,
        ArrayProviderSourceType\ArrayProviderFactory $arrayProviderFactory,
        ColumnDefinitionInterfaceFactory $columnDefinitionFactory,
        DataTypeGuesserInterface $dataTypeGuesser,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->validateArrayProviderConfiguration($gridName, $sourceConfiguration);

        $this->gridName                = $gridName;
        $this->gridSourceDataAccessor  = $gridSourceDataAccessor;
        $this->arrayProviderFactory    = $arrayProviderFactory;
        $this->arrayProviderClass      = $sourceConfiguration['arrayProvider'] ?? '';
        $this->columnDefinitionFactory = $columnDefinitionFactory;
        $this->dataTypeGuesser         = $dataTypeGuesser;
        $this->searchCriteriaBuilder   = $searchCriteriaBuilder;
    }

    private function validateArrayProviderConfiguration(string $gridName, array $sourceConfiguration): void
    {
        $providerClass = $sourceConfiguration['arrayProvider'] ?? '';

        if (!$providerClass) {
            $msg1 = sprintf('No array provider class specified to array provider for grid "%s"', $gridName);
            throw new \InvalidArgumentException($msg1);
        }
        // No check if the provider class exists happens here so virtual types can be used.
    }

    public function getColumnKeys(): array
    {
        return keys($this->getFirstRow());
    }

    public function extractValue($record, string $key)
    {
        if (!array_key_exists($key, $record)) {
            throw new \RuntimeException(sprintf('No column value "%s" on grid row.', $key));
        }
        return $record[$key];
    }

    public function getColumnDefinition(string $key): ColumnDefinitionInterface
    {
        $firstRecord = $this->getFirstRow()[$key] ?? null;
        return $this->columnDefinitionFactory->create([
            'key'  => $key,
            'type' => $this->dataTypeGuesser->valueToTypeCode($firstRecord) ?? '',
        ]);
    }

    /**
     * Other source types should not keep a reference to the grid data.
     *
     * The array grid source type is special and keeps a reference, because it needs to access
     * the first record in order to do column type reflection.
     * Other grid source types should use reflection on the data type classes or the query columns instead.
     * These should not keep a reference to the data so iterators can be used and GC can collect values that are
     * no longer needed.
     *
     * @return RawGridSourceContainer
     */
    public function fetchData(SearchCriteriaInterface $searchCriteria): RawGridSourceContainer
    {
        if (!isset($this->memoizedGridData)) {
            $provider               = $this->arrayProviderFactory->create($this->arrayProviderClass);
            $this->memoizedGridData = $provider->getHyvaGridData();
        }

        $sorted = $this->applySortOrders($this->memoizedGridData, $searchCriteria->getSortOrders() ?? []);
        $page   = $this->selectPage($sorted, $searchCriteria);
        return RawGridSourceContainer::forData($page);
    }

    public function extractRecords(RawGridSourceContainer $rawGridData): array
    {
        return $this->gridSourceDataAccessor->unbox($rawGridData);
    }

    private function getFirstRow(): array
    {
        $searchCriteria = $this->searchCriteriaBuilder->setPageSize(1)->setCurrentPage(1)->create();
        return values($this->gridSourceDataAccessor->unbox($this->fetchData($searchCriteria)))[0] ?? [];
    }

    public function extractTotalRowCount(RawGridSourceContainer $rawGridData): int
    {
        return count($this->memoizedGridData);
    }

    /**
     * @param array $memoizedGridData
     * @param SortOrder[] $getSortOrders
     */
    private function applySortOrders(array $gridData, array $sortOrders): array
    {
        return reduce($sortOrders, [$this, 'applySortOrder'], $gridData);
    }

    private function applySortOrder(array $gridData, SortOrder $sortOrder): array
    {
        if (!($column = $sortOrder->getField())) {
            return $gridData;
        }
        $direction = $sortOrder->getDirection() === SortOrder::SORT_ASC ? 1 : -1;

        usort($gridData, function ($a, $b) use ($column, $direction) {
            return ($a[$column] <=> $b[$column]) * $direction;
        });

        return $gridData;
    }

    private function selectPage(array $gridData, SearchCriteriaInterface $searchCriteria)
    {
        $count = count($gridData);
        $page  = $searchCriteria->getCurrentPage()
            ? $searchCriteria->getCurrentPage() - 1
            : 0;
        $size  = $searchCriteria->getPageSize() ?? $count;
        $start = $page * $size;
        $slice = min($size, max($count - $start, 0));

        return slice($gridData, $start, $slice);
    }
}
