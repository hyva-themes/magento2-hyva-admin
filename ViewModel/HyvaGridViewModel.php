<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel;

use Hyva\Admin\Model\HyvaGridDefinitionInterface;
use Hyva\Admin\Model\HyvaGridDefinitionInterfaceFactory;
use Hyva\Admin\Model\HyvaGridSourceInterface;
use Hyva\Admin\Model\HyvaGridSourceFactory;
use Hyva\Admin\Model\HyvaGridEventDispatcher;
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
    /**
     * @var HyvaGrid\NavigationInterface
     */
    protected $memorizedNaviagtion;
    /**
     * @var \Hyva\Admin\Model\HyvaGridDefinitionInterfaceFactory
     */
    private $gridDefinitionFactory;

    /**
     * @var \Hyva\Admin\ViewModel\HyvaGrid\CellInterfaceFactory
     */
    private $cellFactory;

    /**
     * @var \Hyva\Admin\Model\HyvaGridSourceFactory
     */
    private $gridSourceFactory;

    /**
     * @var \Hyva\Admin\ViewModel\HyvaGrid\RowInterfaceFactory
     */
    private $rowFactory;

    /**
     * @var \Hyva\Admin\ViewModel\HyvaGrid\NavigationInterfaceFactory
     */
    private $navigationFactory;

    /**
     * @var \Hyva\Admin\Model\HyvaGridDefinitionInterface
     */
    private $memoizedGridDefinition;

    /**
     * @var \Hyva\Admin\Model\HyvaGridSourceInterface
     */
    private $memoizedGridSource;

    /**
     * @var \Hyva\Admin\ViewModel\HyvaGrid\EntityDefinitionInterfaceFactory
     */
    private $entityDefinitionFactory;

    /**
     * @var \Hyva\Admin\ViewModel\HyvaGrid\GridActionInterfaceFactory
     */
    private $actionFactory;

    /**
     * @var \Hyva\Admin\ViewModel\HyvaGrid\MassActionInterfaceFactory
     */
    private $massActionFactory;

    /**
     * @var \Hyva\Admin\Model\HyvaGridEventDispatcher
     */
    private $hyvaEventDispatcher;

    /**
     * @var string
     */
    private $gridName;

    /**
     * @var mixed[]
     */
    private $memorizedColumnDefinitions;

    public function __construct(
        string $gridName,
        HyvaGridDefinitionInterfaceFactory $gridDefinitionFactory,
        HyvaGridSourceFactory $gridSourceFactory,
        HyvaGrid\RowInterfaceFactory $rowFactory,
        HyvaGrid\CellInterfaceFactory $cellFactory,
        HyvaGrid\NavigationInterfaceFactory $navigationFactory,
        HyvaGrid\EntityDefinitionInterfaceFactory $entityDefinitionFactory,
        HyvaGrid\GridActionInterfaceFactory $actionFactory,
        HyvaGrid\MassActionInterfaceFactory $massActionFactory,
        HyvaGridEventDispatcher $hyvaPrefetchEventDispatcher
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
        $this->hyvaEventDispatcher     = $hyvaPrefetchEventDispatcher;
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
        if (!isset($this->memorizedColumnDefinitions)) {
            $this->memorizedColumnDefinitions = $this->buildColumnDefinitions();
        }
        return $this->memorizedColumnDefinitions;
    }

    private function buildColumnDefinitions(): array
    {
        $columns          = $this->getGridDefinition()->getIncludedColumns();
        $showAll          = $this->getGridDefinition()->isKeepSourceColumns();
        $keysToHide       = $this->getGridDefinition()->getExcludedColumnKeys();
        $availableColumns = $this->getGridSourceModel()->extractColumnDefinitions($columns, $keysToHide, $showAll);
        $allColumns       = zip($this->getColumnKeys($availableColumns), values($availableColumns));

        return $this->preprocessColumnDefinitions($allColumns);
    }

    private function preprocessColumnDefinitions(array $columnDefinitions): array
    {
        return $this->hyvaEventDispatcher->dispatch(
            $this->gridName,
            'hyva_grid_column_definition_build_after_',
            $columnDefinitions
        );
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
     * @param bool $forceReload
     * @return HyvaGrid\RowInterface[]
     */
    public function getRows(bool $forceReload = false): array
    {
        $searchCriteria = $this->getNavigation()->getSearchCriteria();
        return map([$this, 'buildRow'], $this->getGridSourceModel()->getRecords($searchCriteria, $forceReload));
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
        if (!$this->memorizedNaviagtion) {
            $this->memorizedNaviagtion = $this->navigationFactory->create([
                'gridName'          => $this->getGridName(),
                'gridSource'        => $this->getGridSourceModel(),
                'columnDefinitions' => $this->getColumnDefinitions(),
                'navigationConfig'  => $this->getGridDefinition()->getNavigationConfig(),
            ]);
        }
        return $this->memorizedNaviagtion;
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
            $this->validateActionIdColumnExists((string) $idColumn, 'Action');

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
            ?? (string) keys($this->getColumnDefinitions())[0]
            ?? null;
        $this->validateActionIdColumnExists($idColumn, 'MassActionAction');

        return $idColumn;
    }

    public function getMassActionIdsParam(): ?string
    {
        return $this->getGridDefinition()->getMassActionConfig()['@idsParam'] ?? $this->getMassActionIdColumn();
    }
}
