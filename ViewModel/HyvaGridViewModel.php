<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel;

use Hyva\Admin\Model\HyvaGridDefinitionInterface;
use Hyva\Admin\Model\HyvaGridDefinitionInterfaceFactory;
use Hyva\Admin\Model\HyvaGridSourceInterface;
use Hyva\Admin\Model\HyvaGridSourceFactory;
use Hyva\Admin\ViewModel\HyvaGrid;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\EntityDefinitionInterface;

use function array_combine as zip;
use function array_filter as filter;
use function array_map as map;
use function array_merge as merge;
use function array_values as values;

class HyvaGridViewModel implements HyvaGridInterface
{
    private HyvaGridDefinitionInterfaceFactory $gridDefinitionFactory;

    private HyvaGrid\CellInterfaceFactory $cellFactory;

    private HyvaGridSourceFactory $gridSourceFactory;

    private HyvaGrid\RowInterfaceFactory $rowFactory;

    private HyvaGrid\NavigationInterfaceFactory $navigationFactory;

    private HyvaGridDefinitionInterface $memoizedGridDefinition;

    private HyvaGridSourceInterface $memoizedGridSource;

    private array $memoizedColumnDefinitions;

    private HyvaGrid\EntityDefinitionInterfaceFactory $entityDefinitionFactory;

    private HyvaGrid\ActionInterfaceFactory $actionFactory;

    private HyvaGrid\MassActionInterfaceFactory $massActionFactory;

    private string $gridName;

    public function __construct(
        string $gridName,
        HyvaGridDefinitionInterfaceFactory $gridDefinitionFactory,
        HyvaGridSourceFactory $gridSourceFactory,
        HyvaGrid\RowInterfaceFactory $rowFactory,
        HyvaGrid\CellInterfaceFactory $cellFactory,
        HyvaGrid\NavigationInterfaceFactory $navigationFactory,
        HyvaGrid\EntityDefinitionInterfaceFactory $entityDefinitionFactory,
        HyvaGrid\ActionInterfaceFactory $actionFactory,
        HyvaGrid\MassActionInterfaceFactory $massActionFactory
    ) {
        $this->gridName                = $gridName;
        $this->gridSourceFactory       = $gridSourceFactory;
        $this->gridDefinitionFactory   = $gridDefinitionFactory;
        $this->rowFactory              = $rowFactory;
        $this->cellFactory             = $cellFactory;
        $this->navigationFactory       = $navigationFactory;
        $this->entityDefinitionFactory = $entityDefinitionFactory;
        $this->actionFactory           = $actionFactory;
        $this->massActionFactory       = $massActionFactory;
    }

    private function getGridDefinition(): HyvaGridDefinitionInterface
    {
        if (!isset($this->memoizedGridDefinition)) {
            $this->memoizedGridDefinition = $this->gridDefinitionFactory->create(['gridName' => $this->gridName]);
        }
        return $this->memoizedGridDefinition;
    }

    /**
     * @return ColumnDefinitionInterface[]
     */
    public function getColumnDefinitions(): array
    {
        if (!isset($this->memoizedColumnDefinitions)) {
            $this->memoizedColumnDefinitions = $this->buildColumnDefinitions();
        }
        return $this->memoizedColumnDefinitions;
    }

    private function buildColumnDefinitions(): array
    {
        $columnConfig      = $this->getGridDefinition()->getIncludedColumns();
        // todo: add sortIndex to included columns where not defined
        $keepAllSourceCols = $this->getGridDefinition()->keepColumnsFromSource();
        $available         = $this->getGridSourceModel()->extractColumnDefinitions($columnConfig, $keepAllSourceCols);
        $keysToColumnsMap  = zip($this->getColumnKeys($available), values($available));

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
        $searchCriteria = $this->getNavigation()->getSearchCriteria();
        return map([$this, 'buildRow'], $this->getGridSourceModel()->getRecords($searchCriteria));
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
        return $this->navigationFactory->create([
            'gridSource'   => $this->getGridSourceModel(),
            'navigationConfig' => $this->getGridDefinition()->getNavigationConfig(),
        ]);
    }

    public function getEntityDefinition(): EntityDefinitionInterface
    {
        return $this->entityDefinitionFactory->create([
            'gridName'         => $this->getGridDefinition()->getName(),
            'entityDefinition' => $this->getGridDefinition()->getEntityDefinitionConfig(),
        ]);
    }

    public function getActions(): array
    {
        $actionsConfig = $this->getGridDefinition()->getActionsConfig();

        $actions = map(function (array $actionConfig) use ($actionsConfig): HyvaGrid\ActionInterface {
            $idColumn = $actionsConfig['@idColumn'] ?? null;
            $this->validateActionIdColumnExists($idColumn, 'Action');
            $constructorParams = merge($actionConfig, ['idColumn' => $idColumn]);
            return $this->actionFactory->create($constructorParams);
        }, $actionsConfig['actions'] ?? []);

        $actionIds = map(function (HyvaGrid\ActionInterface $action): string {
            return $action->getId();
        }, $actions);

        return zip($actionIds, $actions);
    }

    private function validateActionIdColumnExists(?string $idColumn, string $actionType): void
    {
        if (isset($idColumn) && !isset($this->getColumnDefinitions()[$idColumn])) {
            throw new \OutOfBoundsException(sprintf('%s ID column "%s" not found.', $actionType, $idColumn));
        }
    }

    public function getRowActionId(): ?string
    {
        return $this->getGridDefinition()->getRowAction() ?? null;
    }

    public function getMassActions(): array
    {
        $massActionsConfig = $this->getGridDefinition()->getMassActionConfig();

        return map(function (array $massActionConfig): HyvaGrid\MassActionInterface {
            return $this->massActionFactory->create($massActionConfig);
        }, $massActionsConfig['actions'] ?? []);
    }

    public function getGridName(): string
    {
        return $this->gridName;
    }

    public function getMassActionIdColumn(): ?string
    {
        $idColumn = $this->getGridDefinition()->getMassActionConfig()['@idColumn'] ?? null;
        $this->validateActionIdColumnExists($idColumn, 'MassActionAction');
        return $idColumn;
    }

    public function getMassActionIdsParam(): ?string
    {
        return $this->getGridDefinition()->getMassActionConfig()['@idsParam'] ?? null;
    }
}
