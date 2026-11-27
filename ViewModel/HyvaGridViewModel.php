<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel;

use Hyva\Admin\Model\GridExport\HyvaGridExportInterface;
use Hyva\Admin\Model\HyvaGridDefinitionInterface;
use Hyva\Admin\Model\HyvaGridDefinitionInterfaceFactory;
use Hyva\Admin\Model\HyvaGridSourceInterface;
use Hyva\Admin\Model\HyvaGridSourceFactory;
use Hyva\Admin\Model\HyvaGridEventDispatcher;
use Hyva\Admin\ViewModel\HyvaGrid;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\EntityDefinitionInterface;

use Hyva\Admin\ViewModel\HyvaGrid\GridExportInterface;
use Hyva\Admin\ViewModel\Shared\JsEventInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;
use function array_combine as zip;
use function array_filter as filter;
use function array_keys as keys;
use function array_map as map;
use function array_merge as merge;
use function array_reduce as reduce;
use function array_values as values;

class HyvaGridViewModel implements HyvaGridInterface, HyvaGridExportInterface
{
    /**
     * @var HyvaGrid\NavigationInterface
     */
    private $memoizedNavigation;

    /**
     * @var HyvaGridDefinitionInterfaceFactory
     */
    private $gridDefinitionFactory;

    /**
     * @var HyvaGrid\CellInterfaceFactory
     */
    private $cellFactory;

    /**
     * @var HyvaGridSourceFactory
     */
    private $gridSourceFactory;

    /**
     * @var HyvaGrid\RowInterfaceFactory
     */
    private $rowFactory;

    /**
     * @var HyvaGrid\NavigationInterfaceFactory
     */
    private $navigationFactory;

    /**
     * @var HyvaGridDefinitionInterface
     */
    private $memoizedGridDefinition;

    /**
     * @var HyvaGridSourceInterface
     */
    private $memoizedGridSource;

    /**
     * @var HyvaGrid\EntityDefinitionInterfaceFactory
     */
    private $entityDefinitionFactory;

    /**
     * @var HyvaGrid\GridActionInterfaceFactory
     */
    private $actionFactory;

    /**
     * @var HyvaGrid\MassActionInterfaceFactory
     */
    private $massActionFactory;

    /**
     * @var HyvaGridEventDispatcher
     */
    private $hyvaEventDispatcher;

    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var string
     */
    private $gridName;

    /**
     * @var array
     */
    private $memoizedColumnDefinitions;

    /**
     * @var HyvaGrid\GridJsEventFactory
     */
    private $gridJsEventFactory;

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
        HyvaGrid\GridJsEventFactory $gridJsEventFactory,
        HyvaGridEventDispatcher $hyvaPrefetchEventDispatcher,
        LayoutInterface $layout
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
        $this->layout                  = $layout;
        $this->gridJsEventFactory      = $gridJsEventFactory;
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

    public function getRows(): array
    {
        return $this->getRowsForSearchCriteria($this->getNavigation()->getSearchCriteria());
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
        if (!isset($this->memoizedNavigation)) {
            $this->memoizedNavigation = $this->navigationFactory->create([
                'gridName'          => $this->getGridName(),
                'gridSource'        => $this->getGridSourceModel(),
                'columnDefinitions' => $this->getColumnDefinitions(),
                'navigationConfig'  => $this->getGridDefinition()->getNavigationConfig(),
            ]);
        }
        return $this->memoizedNavigation;
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

            $events       = $this->buildActionJsEvents($actionConfig);

            return $this->actionFactory->create(merge($actionConfig, ['idColumn' => $idColumn, 'events' => $events]));
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

    private function buildActionJsEvents(array $actionConfig): array
    {
        $eventsConfig = $actionConfig['events'] ?? [];
        return map(function (string $on) use ($actionConfig): JsEventInterface {
            return $this->gridJsEventFactory->create([
                'on'       => $on,
                'gridName' => $this->gridName,
                'targetId' => $actionConfig['id'] ?? $actionsConfig['label'] ?? '',
            ]);
        }, keys($eventsConfig));
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
            ?? $this->getFirstColumnKey()
            ?? null;
        $this->validateActionIdColumnExists($idColumn, 'MassActionAction');

        return $idColumn;
    }

    public function getFirstColumnKey(): ?string
    {
        return isset($this->getColumnDefinitions()[0])
            ? (string) $this->getColumnDefinitions()[0]
            : null;
    }

    public function getMassActionIdsParam(): ?string
    {
        return $this->getGridDefinition()->getMassActionConfig()['@idsParam'] ?? $this->getMassActionIdColumn();
    }

    public function getColumnToggleHtml(): string
    {
        $renderer = $this->createRenderer();
        $renderer->setTemplate('Hyva_Admin::grid/column-toggle.phtml');
        return $renderer->toHtml();
    }

    public function getExportsHtml(): string
    {
        $renderer = $this->createRenderer();
        $renderer->setTemplate('Hyva_Admin::grid/exports.phtml');
        $renderer->assign('navigation', $this->getNavigation());
        $renderer->assign('exports', $this->getNavigation()->getExports());
        return $renderer->toHtml();
    }

    private function createRenderer(): Template
    {
        return $this->layout->createBlock(Template::class);
    }

    public function getSearchCriteria(): SearchCriteriaInterface
    {
        return $this->getNavigation()->getSearchCriteria();
    }

    public function getRowsForSearchCriteria(SearchCriteriaInterface $searchCriteria): array
    {
        return map([$this, 'buildRow'], $this->getGridSourceModel()->getRecords($searchCriteria));
    }

    public function getTotalRowsCount(): int
    {
        return $this->getNavigation()->getTotalRowsCount();
    }

    public function getExport(string $exportType): ?GridExportInterface
    {
        return $this->getNavigation()->getExports()[$exportType] ?? null;
    }
}
