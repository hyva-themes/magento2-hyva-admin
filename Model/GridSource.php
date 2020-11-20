<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;

use function array_combine as zip;
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
            return $this->mergeColumnDefinitions($extractedDefinition, $mapKeyToDefinitions[$key] ?? null);
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
            ? $this->columnDefinitionFactory->create(merge($columnA->toArray(), $columnB->toArray()))
            : $columnA;
    }

    // todo: receive paging and filtering info here and pass it to getRawGridData
    public function getRecords(): array
    {
        return $this->gridSourceType->extractRecords($this->getRawGridData());
    }

    public function extractValue($record, string $key)
    {
        return $this->gridSourceType->extractValue($record, $key);
    }

    private function getRawGridData(): RawGridSourceContainer
    {
        // todo: receive paging and filtering data and pass it to fetchData
        if (!isset($this->rawGridData)) {
            $this->rawGridData = $this->gridSourceType->fetchData();
        }
        return $this->rawGridData;
    }
}
