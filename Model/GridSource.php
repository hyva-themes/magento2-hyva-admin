<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;
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
     * @var \Hyva\Admin\Model\GridSourceType\GridSourceTypeInterface
     */
    private $gridSourceType;

    /**
     * @var \Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory
     */
    private $columnDefinitionFactory;

    /**
     * @var \Hyva\Admin\Model\RawGridSourceContainer
     */
    private $rawGridData;

    public function __construct(
        GridSourceType\GridSourceTypeInterface $gridSourceType,
        ColumnDefinitionInterfaceFactory $columnDefinitionFactory
    ) {
        $this->gridSourceType          = $gridSourceType;
        $this->columnDefinitionFactory = $columnDefinitionFactory;
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
        $extractedArray   = $extracted->toArray();
        $isVisibleArray   = ['isVisible' => $isVisible];
        return $this->columnDefinitionFactory->create(merge($extractedArray, $configuredArray, $isVisibleArray));
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
        if (!isset($this->rawGridData)) {
            $this->rawGridData = $this->gridSourceType->fetchData($searchCriteria);
        }
        return $this->rawGridData;
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
                $sortOrder = $column->getSortOrder() ? $column->getSortOrder() : (string) $nextSortOrder;
                return $this->columnDefinitionFactory->create(merge($column->toArray(), ['sortOrder' => $sortOrder]));
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
