<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\EntityDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\NavigationInterface;
use Hyva\Admin\ViewModel\HyvaGrid\RowInterface;

interface HyvaGridInterface
{
    /**
     * @return ColumnDefinitionInterface[]
     */
    public function getColumnDefinitions(): array;

    /**
     * @return RowInterface[]
     */
    public function getRows(): array;

    public function getNavigation(): NavigationInterface;

    public function getColumnCount(): int;

    public function getEntityDefinition(): EntityDefinitionInterface;
}
