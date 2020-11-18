<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel;

interface HyvaGridInterface
{
    /**
     * @return HyvaGrid\ColumnDefinitionInterface[]
     */
    public function getColumnDefinitions(): array;

    /**
     * @return HyvaGrid\RowInterface[]
     */
    public function getRows(): array;

    public function getNavigation(): HyvaGrid\NavigationInterface;

    public function getColumnCount(): int;

    public function getEntityDefinition(): HyvaGrid\EntityDefinitionInterface;

    /**
     * @return HyvaGrid\ActionInterface[]
     */
    public function getActions(): array;

    public function getRowActionId(): ?string;
}
