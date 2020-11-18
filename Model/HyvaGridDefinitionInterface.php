<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;

interface HyvaGridDefinitionInterface
{
    public function getName(): string;

    /**
     * @return ColumnDefinitionInterface[]
     */
    public function getIncludedColumns(): array;

    /**
     * @return string[]
     */
    public function getExcludedColumnKeys(): array;

    public function getSourceConfig(): array;

    public function getEntityDefinitionConfig(): array;

    public function getActionsConfig(): array;

    public function getRowAction(): ?string;
}
