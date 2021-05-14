<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use Hyva\Admin\Api\DataTypeGuesserInterface;
use Hyva\Admin\Api\HyvaGridSourceProcessorInterface;
use Hyva\Admin\Model\RawGridSourceContainer;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;

use function array_filter as filter;
use function array_keys as keys;
use function array_map as map;
use function array_reduce as reduce;
use function array_slice as slice;
use function array_values as values;

class ArrayProviderGridSourceType implements GridSourceTypeInterface
{
    /**
     * @var RawGridSourceDataAccessor
     */
    private $gridSourceDataAccessor;

    /**
     * @var ArrayProviderSourceType\ArrayProviderFactory
     */
    private $arrayProviderFactory;

    /**
     * @var ColumnDefinitionInterfaceFactory
     */
    private $columnDefinitionFactory;

    /**
     * @var DataTypeGuesserInterface
     */
    private $dataTypeGuesser;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var array[]
     */
    private $memoizedGridData;

    /**
     * @var string
     */
    private $arrayProviderClass;

    /**
     * @var string
     */
    private $gridName;

    /**
     * @var HyvaGridSourceProcessorInterface[]
     */
    private $processors;

    public function __construct(
        string $gridName,
        array $sourceConfiguration,
        RawGridSourceDataAccessor $gridSourceDataAccessor,
        ArrayProviderSourceType\ArrayProviderFactory $arrayProviderFactory,
        ColumnDefinitionInterfaceFactory $columnDefinitionFactory,
        DataTypeGuesserInterface $dataTypeGuesser,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        array $processors = []
    ) {

        $this->gridName                = $gridName;
        $this->processors              = $processors;
        $this->gridSourceDataAccessor  = $gridSourceDataAccessor;
        $this->arrayProviderFactory    = $arrayProviderFactory;
        $this->arrayProviderClass      = $sourceConfiguration['arrayProvider'] ?? '';
        $this->columnDefinitionFactory = $columnDefinitionFactory;
        $this->dataTypeGuesser         = $dataTypeGuesser;
        $this->searchCriteriaBuilder   = $searchCriteriaBuilder;

        $this->validateArrayProviderConfiguration($sourceConfiguration);
    }

    public function getRecordType(): string
    {
        return 'array';
    }

    private function validateArrayProviderConfiguration(array $sourceConfiguration): void
    {
        $providerClass = $sourceConfiguration['arrayProvider'] ?? '';

        if (!$providerClass) {
            $msg1 = sprintf('No array provider class specified to array provider for grid "%s"', $this->gridName);
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
        return $record[$key] ?? null;
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
            $provider = $this->arrayProviderFactory->create($this->arrayProviderClass);

            map(function (HyvaGridSourceProcessorInterface $processor) use ($provider, $searchCriteria): void {
                $processor->beforeLoad($provider, $searchCriteria, $this->gridName);
            }, $this->processors);

            $filterGroups = $searchCriteria->getFilterGroups() ?? [];

            $result = reduce(
                $this->processors,
                function (array $result, HyvaGridSourceProcessorInterface $processor) use ($searchCriteria): array {
                    return $processor->afterLoad($result, $searchCriteria, $this->gridName) ?? $result;
                },
                $this->applyFilterGroups($provider->getHyvaGridData(), $filterGroups)
            );

            $this->memoizedGridData = $result;
        }

        $gridData = $this->applyPagination($this->memoizedGridData, $searchCriteria);

        return RawGridSourceContainer::forData($gridData);
    }

    private function applyPagination(array $gridData, SearchCriteriaInterface $searchCriteria): array
    {
        $sorted = $this->applySortOrders($gridData, $searchCriteria->getSortOrders() ?? []);
        return $this->selectPage($sorted, $searchCriteria);
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
            return (($a[$column] ?? 0) <=> ($b[$column] ?? 0)) * $direction;
        });

        return $gridData;
    }

    private function selectPage(array $gridData, SearchCriteriaInterface $searchCriteria): array
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

    /**
     * @param array[] $gridData
     * @param FilterGroup[] $filterGroups
     * @return array[]
     */
    private function applyFilterGroups(array $gridData, array $filterGroups): array
    {
        return values(filter(reduce($filterGroups, [$this, 'applyFilterGroup'], $gridData)));
    }

    private function applyFilterGroup(array $gridData, FilterGroup $filterGroup): array
    {
        return filter($gridData, function (array $record) use ($filterGroup): bool {
            return $this->hasAnyMatchingFilter($record, $filterGroup->getFilters() ?? []);
        });
    }

    private function hasAnyMatchingFilter(array $record, array $filters): bool
    {
        return reduce($filters, function (bool $hasMatch, Filter $filter) use ($record): bool {
            return $hasMatch || $this->isMatchingFilter($record, $filter);
        }, false);
    }

    private function isMatchingFilter(array $record, Filter $filter): bool
    {
        $fieldValue  = $record[$filter->getField()] ?? null;
        $filterValue = $filter->getValue();
        switch ($filter->getConditionType()) {
            case 'eq':
            case 'is':
                return $fieldValue === $filterValue;
            case 'neq':
                return $fieldValue !== $filterValue;
            case 'lteq':
            case 'to':
                return $fieldValue <= $filterValue;
            case 'gteq':
            case 'moreq':
            case 'from':
                return $fieldValue >= $filterValue;
            case 'gt':
                return $fieldValue > $filterValue;
            case 'lt':
                return $fieldValue < $filterValue;
            case 'like':
                return (bool) preg_match($this->likeExpressionToRegex($filterValue), $fieldValue);
            case 'nlike':
                return !preg_match($this->likeExpressionToRegex($filterValue), $fieldValue);
            case 'in':
                return in_array($filterValue, $fieldValue);
            case 'nin':
                return !in_array($filterValue, $fieldValue);
            case 'notnull':
                return isset($fieldValue);
            case 'null':
                return is_null($fieldValue);
            case 'finset':
                return (bool) preg_match($this->findInSetFilterToRegex($filterValue), $fieldValue);
            default:
                throw new \OutOfBoundsException(sprintf(
                    'Filter condition "%s" is not (currently) supported by array grid data providers',
                    $filter->getConditionType()
                ));
        }
    }

    private function likeExpressionToRegex(string $filterValue): string
    {
        $percentWildcards              = preg_replace('#(?<!\\\)%#', '.*', $filterValue);
        $percentAndUnderscoreWildcards = preg_replace('#(?<!\\\)_#', '.', $percentWildcards);

        return '/^' . $percentAndUnderscoreWildcards . '$/i';
    }

    private function findInSetFilterToRegex(string $filterValue): string
    {
        $matchOptions = [
            'only'          => $filterValue,
            'start of list' => $filterValue . ',.+',
            'in list'       => '.+,' . $filterValue . ',.+',
            'end of list'   => '.+,' . $filterValue,
        ];

        return '/^(?:' . implode('|', $matchOptions) . ')$/i';
    }

    public function getGridName(): string
    {
        return $this->gridName;
    }
}
