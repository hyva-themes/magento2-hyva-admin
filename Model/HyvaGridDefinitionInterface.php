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

    /**
     * @return mixed[]
     */
    public function getSourceConfig(): array;

    /**
     * @return mixed[]
     */
    public function getEntityDefinitionConfig(): array;

    /**
     * @return mixed
     */
    public function getActionsConfig(): array;

    public function getRowAction(): ?string;

    /**
     * @return mixed[]
     */
    public function getMassActionConfig(): array;

    public function isKeepSourceColumns(): bool;

    public function getNavigationConfig(): array;
}
