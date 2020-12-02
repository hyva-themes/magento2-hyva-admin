<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel;

interface HyvaGridInterface
{
    public function getGridName(): string;

    /**
     * @return HyvaGrid\ColumnDefinitionInterface[]
     */
    public function getColumnDefinitions(): array;

    /**
     * @return HyvaGrid\ColumnDefinitionInterface[]
     */
    public function getAllColumnDefinitions(): array;

    /**
     * @return HyvaGrid\RowInterface[]
     */
    public function getRows(): array;

    public function getNavigation(): HyvaGrid\NavigationInterface;

    public function getColumnCount(): int;

    public function getEntityDefinition(): HyvaGrid\EntityDefinitionInterface;

    /**
     * @return HyvaGrid\GridActionInterface[]
     */
    public function getActions(): array;

    public function getRowActionId(): ?string;

    /**
     * @return HyvaGrid\MassActionInterface[]
     */
    public function getMassActions(): array;

    public function getMassActionIdColumn(): ?string;

    public function getMassActionIdsParam(): ?string;
}
