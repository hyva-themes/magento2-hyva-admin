<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;

use Magento\Framework\Api\SearchCriteriaInterface;
use function array_combine as zip;
use function array_filter as filter;
use function array_map as map;
use function array_merge as merge;
use function array_values as values;

class GridSource implements HyvaGridSourceInterface
{
    private GridSourceType\GridSourceTypeInterface $gridSourceType;

    private ColumnDefinitionInterfaceFactory $columnDefinitionFactory;

    private RawGridSourceContainer $rawGridData;

    public function __construct(
        GridSourceType\GridSourceTypeInterface $gridSourceType,
        ColumnDefinitionInterfaceFactory $columnDefinitionFactory
    ) {
        $this->gridSourceType          = $gridSourceType;
        $this->columnDefinitionFactory = $columnDefinitionFactory;
    }

    /**
     * @param ColumnDefinitionInterface[] $includeConfig
     * @param bool $keepAllSourceCols
     * @return ColumnDefinitionInterface[]
     */
    public function extractColumnDefinitions(array $includeConfig, bool $keepAllSourceCols = false): array
    {
        $configuredKeys                = $this->extractKeys(...values($includeConfig));
        $mapKeyToDefinitions           = zip($configuredKeys, values($includeConfig));
        $availableColumnKeysFromSource = $this->gridSourceType->getColumnKeys();

        $this->validateConfiguredKeys($configuredKeys, $availableColumnKeysFromSource);

        $columnKeys = empty($mapKeyToDefinitions) || $keepAllSourceCols
            ? $availableColumnKeysFromSource
            : $configuredKeys;

        return map(function (string $key) use ($mapKeyToDefinitions): ColumnDefinitionInterface {
            $extractedDefinition = $this->gridSourceType->getColumnDefinition($key);
            $mergedDefinition    = $this->mergeColumnDefinitions($extractedDefinition, $mapKeyToDefinitions[$key] ?? null);
            return $mergedDefinition;
        }, $columnKeys);
    }

    /**
     * @param ColumnDefinitionInterface ...$columnDefinitions
     * @return string[]
     */
    private function extractKeys(ColumnDefinitionInterface ...$columnDefinitions): array
    {
        return map(function (ColumnDefinitionInterface $columnDefinition): string {
            return $columnDefinition->getKey();
        }, $columnDefinitions);
    }

    private function validateConfiguredKeys(array $configuredKeys, array $availableColumnKeysFromSource): void
    {
        if ($missing = array_diff($configuredKeys, $availableColumnKeysFromSource)) {
            throw new \OutOfBoundsException(sprintf('Column(s) not found on source: %s', implode(', ', $missing)));
        }
    }

    private function mergeColumnDefinitions(
        ColumnDefinitionInterface $columnA,
        ?ColumnDefinitionInterface $columnB
    ): ColumnDefinitionInterface {
        return $columnB
            ? $this->columnDefinitionFactory->create(merge($columnA->toArray(), filter($columnB->toArray())))
            : $columnA;
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
}
