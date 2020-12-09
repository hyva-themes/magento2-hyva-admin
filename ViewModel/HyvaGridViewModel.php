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
use function array_keys as keys;
use function array_map as map;
use function array_merge as merge;
use function array_reduce as reduce;
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

    private HyvaGrid\EntityDefinitionInterfaceFactory $entityDefinitionFactory;

    private HyvaGrid\GridActionInterfaceFactory $actionFactory;

    private HyvaGrid\MassActionInterfaceFactory $massActionFactory;

    private string $gridName;

    private array $memoizedColumnDefinitions;

    public function __construct(
        string $gridName,
        HyvaGridDefinitionInterfaceFactory $gridDefinitionFactory,
        HyvaGridSourceFactory $gridSourceFactory,
        HyvaGrid\RowInterfaceFactory $rowFactory,
        HyvaGrid\CellInterfaceFactory $cellFactory,
        HyvaGrid\NavigationInterfaceFactory $navigationFactory,
        HyvaGrid\EntityDefinitionInterfaceFactory $entityDefinitionFactory,
        HyvaGrid\GridActionInterfaceFactory $actionFactory,
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

    public function getAllColumnDefinitions(): array
    {
        if (!isset($this->memoizedColumnDefinitions)) {
            $this->memoizedColumnDefinitions = $this->buildColumnDefinitions();
        }
        return $this->memoizedColumnDefinitions;
    }

    private function buildColumnDefinitions(): array
    {
        $columns          = $this->getGridDefinition()->getIncludedColumns();
        $showAll          = $this->getGridDefinition()->isKeepSourceColumns();
        $keysToHide       = $this->getGridDefinition()->getExcludedColumnKeys();
        $availableColumns = $this->getGridSourceModel()->extractColumnDefinitions($columns, $keysToHide, $showAll);

        return zip($this->getColumnKeys($availableColumns), values($availableColumns));
    }

    public function getColumnDefinitions(): array
    {
        return $this->removeHiddenColumns($this->getAllColumnDefinitions());
    }

    private function removeHiddenColumns(array $columns): array
    {
        return filter($columns, function (ColumnDefinitionInterface $columnDefinition): bool {
            return $columnDefinition->isVisible();
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
        $cells         = $this->buildCells($record);
        $cellsWithRows = $this->addRowReferenceToCells($cells);
        return $this->rowFactory->create(['cells' => $cellsWithRows]);
    }

    /**
     * @param mixed $record
     * @return HyvaGrid\CellInterface[]
     */
    private function buildCells($record): array
    {
        return map(function (ColumnDefinitionInterface $columnDefinition) use ($record): HyvaGrid\CellInterface {
            // no lazy evaluation so the reference to $record can be freed
            $value = $this->getGridSourceModel()->extractValue($record, $columnDefinition->getKey());
            return $this->cellFactory->create(['value' => $value, 'columnDefinition' => $columnDefinition]);
        }, $this->getAllColumnDefinitions());
    }

    private function addRowReferenceToCells(array $cells): array
    {
        return map(function (HyvaGrid\CellInterface $cell) use ($cells): HyvaGrid\CellInterface {
            return $this->createCellWithRowExcludingCell($cell, $cells);
        }, $cells);
    }

    private function createCellWithRowExcludingCell(HyvaGrid\CellInterface $cell, array $cells): HyvaGrid\CellInterface
    {
        unset($cells[$cell->getColumnDefinition()->getKey()]);
        return $this->cellFactory->create([
            'value'            => $cell->getRawValue(),
            'columnDefinition' => $cell->getColumnDefinition(),
            'row'              => $this->rowFactory->create(['cells' => $cells]),
        ]);
    }

    public function getNavigation(): HyvaGrid\NavigationInterface
    {
        return $this->navigationFactory->create([
            'gridName'          => $this->getGridName(),
            'gridSource'        => $this->getGridSourceModel(),
            'columnDefinitions' => $this->getColumnDefinitions(),
            'navigationConfig'  => $this->getGridDefinition()->getNavigationConfig(),
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

        $actions = map(function (array $actionConfig) use ($actionsConfig): HyvaGrid\GridActionInterface {
            $idColumn = $actionsConfig['@idColumn'] ?? null;
            $this->validateActionIdColumnExists($idColumn, 'Action');

            return $this->actionFactory->create(merge($actionConfig, ['idColumn' => $idColumn]));
        }, $actionsConfig['actions'] ?? []);

        $actionIds = map(function (HyvaGrid\GridActionInterface $action): string {
            return $action->getId();
        }, $actions);

        return zip($actionIds, $actions);
    }

    private function validateActionIdColumnExists(?string $idColumn, string $actionType): void
    {
        if (isset($idColumn) && !isset($this->getAllColumnDefinitions()[$idColumn])) {
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
        $idColumn = $this->getGridDefinition()->getMassActionConfig()['@idColumn']
            ?? keys($this->getColumnDefinitions())[0]
            ?? null;
        $this->validateActionIdColumnExists($idColumn, 'MassActionAction');

        return $idColumn;
    }

    public function getMassActionIdsParam(): ?string
    {
        return $this->getGridDefinition()->getMassActionConfig()['@idsParam'] ?? $this->getMassActionIdColumn();
    }
}
