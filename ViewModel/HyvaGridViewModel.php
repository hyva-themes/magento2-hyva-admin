<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel;

use Hyva\Admin\Model\HyvaGridDefinitionInterface;
use Hyva\Admin\Model\HyvaGridDefinitionInterfaceFactory;
use Hyva\Admin\Model\HyvaGridSourceInterface;
use Hyva\Admin\Model\HyvaGridSourceFactory;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid;

use function array_combine as zip;
use function array_filter as filter;
use function array_map as map;
use function array_values as values;

class HyvaGridViewModel implements HyvaGridInterface
{
    private HyvaGridDefinitionInterfaceFactory $gridDefinitionFactory;

    private HyvaGrid\CellInterfaceFactory $cellFactory;

    private HyvaGridSourceFactory $gridSourceFactory;

    private HyvaGrid\RowInterfaceFactory $rowFactory;

    private HyvaGrid\NavigationInterfaceFactory $navigationFactory;

    private string $gridName;

    public function __construct(
        string $gridName,
        HyvaGridDefinitionInterfaceFactory $gridDefinitionFactory,
        HyvaGridSourceFactory $gridSourceFactory,
        HyvaGrid\RowInterfaceFactory $rowFactory,
        HyvaGrid\CellInterfaceFactory $cellFactory,
        HyvaGrid\NavigationInterfaceFactory $navigationFactory
    ) {
        $this->gridName              = $gridName;
        $this->gridSourceFactory     = $gridSourceFactory;
        $this->gridDefinitionFactory = $gridDefinitionFactory;
        $this->rowFactory            = $rowFactory;
        $this->cellFactory           = $cellFactory;
        $this->navigationFactory     = $navigationFactory;
    }

    private function getGridDefinition(): HyvaGridDefinitionInterface
    {
        return $this->gridDefinitionFactory->create(['gridName' => $this->gridName]);
    }

    /**
     * @return ColumnDefinitionInterface[]
     */
    public function getColumnDefinitions(): array
    {
        $columnConfig     = $this->getGridDefinition()->getIncludedColumns();
        $available        = $this->getGridSourceModel()->extractColumnDefinitions($columnConfig);
        $keysToColumnsMap = zip($this->getColumnKeys($available), values($available));

        return $this->removeColumns($keysToColumnsMap, $this->getGridDefinition()->getExcludedColumnKeys());

    }

    /**
     * @param ColumnDefinitionInterface[] $columns
     * @param string[] $removeKeys
     * @return ColumnDefinitionInterface[]
     */
    private function removeColumns(array $columns, array $removeKeys): array
    {
        return filter($columns, function (ColumnDefinitionInterface $column) use ($removeKeys): bool {
            return !in_array($column->getKey(), $removeKeys);
        });
    }

    private function getColumnKeys(array $columnDefinitions): array
    {
        return map(function (ColumnDefinitionInterface $columnDefinition): string {
            return $columnDefinition->getKey();
        }, $columnDefinitions);
    }

    public function getColumnCount(): int
    {
        return count($this->getColumnDefinitions());
    }

    private function getGridSourceModel(): HyvaGridSourceInterface
    {
        if (!isset($this->memoizedGridSource)) {
            $this->memoizedGridSource = $this->gridSourceFactory->createFor($this->getGridDefinition());
        }
        return $this->memoizedGridSource;
    }

    /**
     * @return HyvaGrid\RowInterface[]
     */
    public function getRows(): array
    {
        // todo: this is where paging related information would have to be passed to the source model
        return map([$this, 'buildRow'], $this->getGridSourceModel()->getRecords());
    }

    private function buildRow($record): HyvaGrid\RowInterface
    {
        return $this->rowFactory->create(['cells' => $this->buildCells($record)]);
    }

    /**
     * @param $record
     * @return HyvaGrid\CellInterface[]
     */
    private function buildCells($record): array
    {
        return map(function (ColumnDefinitionInterface $columnDefinition) use ($record): HyvaGrid\CellInterface {
            // no lazy evaluation so the reference to $record can be freed
            $value = $this->getGridSourceModel()->extractValue($record, $columnDefinition->getKey());
            return $this->cellFactory->create(['value' => $value, 'columnDefinition' => $columnDefinition]);
        }, $this->getColumnDefinitions());
    }

    public function getNavigation(): HyvaGrid\NavigationInterface
    {
        return $this->navigationFactory->create(['gridSource' => $this->getGridSourceModel()]);
    }
}
