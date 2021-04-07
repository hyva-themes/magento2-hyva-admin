<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Model\GridSource\SearchCriteriaBindings;
use Hyva\Admin\Model\GridSource\SearchCriteriaIdentity;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

use function array_combine as zip;
use function array_diff as diff;
use function array_filter as filter;
use function array_keys as keys;
use function array_map as map;
use function array_merge as merge;
use function array_reduce as reduce;
use function array_slice as slice;

class GridSource implements HyvaGridSourceInterface
{
    /**
     * @var GridSourceType\GridSourceTypeInterface
     */
    private $gridSourceType;

    /**
     * @var RawGridSourceContainer
     */
    private $rawGridData;

    private $rawGridDataSearchCriteriaHash = '';

    /**
     * @var GridSourcePrefetchEventDispatcher
     */
    private $gridSourcePrefetchEventDispatcher;

    /**
     * @var SearchCriteriaBindings
     */
    private $defaultSearchCriteriaBindings;

    /**
     * @var string
     */
    private $gridName;

    /**
     * @var SearchCriteriaIdentity
     */
    private $searchCriteriaIdentity;

    public function __construct(
        string $gridName,
        GridSourceType\GridSourceTypeInterface $gridSourceType,
        GridSourcePrefetchEventDispatcher $gridSourcePrefetchEventDispatcher,
        SearchCriteriaBindings $searchCriteriaBindings,
        SearchCriteriaIdentity $searchCriteriaIdentity
    ) {
        $this->gridName                          = $gridName;
        $this->gridSourceType                    = $gridSourceType;
        $this->gridSourcePrefetchEventDispatcher = $gridSourcePrefetchEventDispatcher;
        $this->defaultSearchCriteriaBindings     = $searchCriteriaBindings;
        $this->searchCriteriaIdentity            = $searchCriteriaIdentity;
    }

    public function extractColumnDefinitions(array $configuredColumns, array $hiddenKeys, bool $keepAll): array
    {
        // Algorithm defining the sortOrder on grids:
        // 1. add sortOrder (larger than others) to all configured include columns without a specific sortOrder (pass 1)
        // 2. add sortOrder (larger than others) to all extracted columns that where not included (pass 2)
        // 3. sort columns (maybe in grid view model...?)

        $configuredColumns = $this->addMissingSortOrder($configuredColumns);
        $allColumnKeys     = $this->gridSourceType->getColumnKeys();

        $this->validateConfiguredKeys(keys($configuredColumns), $allColumnKeys);

        $invisible = $keepAll || empty($configuredColumns)
            ? $hiddenKeys
            : diff($allColumnKeys, keys($configuredColumns));

        $extractedColumns = map(function (string $key) use ($configuredColumns, $invisible): ColumnDefinitionInterface {
            $extractedDefinition  = $this->gridSourceType->getColumnDefinition($key);
            $configuredDefinition = $configuredColumns[$key] ?? null;
            $isVisible            = !in_array($key, $invisible, true);
            return $this->mergeColumnDefinitions($extractedDefinition, $configuredDefinition, $isVisible);
        }, zip($allColumnKeys, $allColumnKeys));

        $extractedColumnsWithSortOrder = $this->addMissingSortOrder($extractedColumns);
        return $this->sortColumns($extractedColumnsWithSortOrder);
    }

    private function validateConfiguredKeys(array $configuredKeys, array $availableColumnKeysFromSource): void
    {
        if ($missing = array_diff($configuredKeys, $availableColumnKeysFromSource)) {
            throw new \OutOfBoundsException(sprintf('Column(s) not found on source: %s', implode(', ', $missing)));
        }
    }

    private function mergeColumnDefinitions(
        ColumnDefinitionInterface $extracted,
        ?ColumnDefinitionInterface $configured,
        bool $isVisible
    ): ColumnDefinitionInterface {
        $configuredArray = $configured ? filter($configured->toArray()) : [];
        $isVisibleArray  = ['isVisible' => $isVisible];

        return $extracted->merge(merge($configuredArray, $isVisibleArray));
    }

    public function getRecords(SearchCriteriaInterface $searchCriteria): array
    {
        return $this->gridSourceType->extractRecords($this->getRawGridData($searchCriteria));
    }

    public function extractValue($record, string $key)
    {
        return $this->gridSourceType->extractValue($record, $key);
    }

    private function getRawGridData(SearchCriteriaInterface $searchCriteria): RawGridSourceContainer
    {
        $preprocessedSearchCriteria = $this->preprocessSearchCriteria($searchCriteria);
        $searchCriteriaHash         = $this->searchCriteriaIdentity->hash($preprocessedSearchCriteria);
        if ($this->rawGridDataSearchCriteriaHash !== $searchCriteriaHash) {
            $this->rawGridDataSearchCriteriaHash = $searchCriteriaHash;
            $this->rawGridData                   = $this->gridSourceType->fetchData($preprocessedSearchCriteria);
        }
        return $this->rawGridData;
    }

    private function preprocessSearchCriteria(SearchCriteriaInterface $searchCriteria): SearchCriteriaInterface
    {
        return $this->gridSourcePrefetchEventDispatcher->dispatch(
            $this->gridName,
            $this->gridSourceType->getRecordType(),
            $this->defaultSearchCriteriaBindings->apply($searchCriteria)
        );
    }

    public function getTotalCount(SearchCriteriaInterface $searchCriteria): int
    {
        return $this->gridSourceType->extractTotalRowCount($this->getRawGridData($searchCriteria));
    }

    /**
     * @param ColumnDefinitionInterface[] $includeConfig
     * @return int
     */
    private function getMaxSortOrder(array $includeConfig): int
    {
        return reduce($includeConfig, function (int $maxSortOrder, ColumnDefinitionInterface $column): int {
            return max($maxSortOrder, $column->getSortOrder());
        }, 0);
    }

    /**
     * Add sortOrder to all columns that don't have a sortOrder already
     *
     * The generated sortOrder values are larger than the largest specified sortOrder.
     *
     * @param ColumnDefinitionInterface[] $columns
     * @return ColumnDefinitionInterface[]
     */
    private function addMissingSortOrder(array $columns): array
    {
        $currentMaxSortOrder  = $this->getMaxSortOrder($columns);
        $nextSortOrders       = range($currentMaxSortOrder + 1, $currentMaxSortOrder + count($columns));
        $columnsWithSortOrder = map(
            function (ColumnDefinitionInterface $column, int $nextSortOrder): ColumnDefinitionInterface {
                $sortOrder = (string) ($column->getSortOrder() ? $column->getSortOrder() : $nextSortOrder);
                return $column->merge(['sortOrder' => $sortOrder]);
            },
            $columns,
            slice($nextSortOrders, 0, count($columns))
        );
        return zip(keys($columns), $columnsWithSortOrder);
    }

    /**
     * @param ColumnDefinitionInterface[] $columns
     * @return ColumnDefinitionInterface[]
     */
    private function sortColumns(array $columns): array
    {
        uasort($columns, function (ColumnDefinitionInterface $a, ColumnDefinitionInterface $b) {
            return $a->getSortOrder() <=> $b->getSortOrder();
        });

        return $columns;
    }
}
