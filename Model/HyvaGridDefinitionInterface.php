<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;

interface HyvaGridDefinitionInterface
{
    /**
     * @return ColumnDefinitionInterface[]
     */
    public function getIncludedColumns(): array;

    /**
     * @return string[]
     */
    public function getExcludedColumnKeys(): array;

    public function getSourceConfig(): array;
}
