<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;

interface HyvaGridSourceInterface
{
    /**
     * @param ColumnDefinitionInterface[] $includeConfig
     * @return ColumnDefinitionInterface[]
     */
    public function extractColumnDefinitions(array $includeConfig): array;

    public function getRecords(): array;

    public function extractValue($record, string $key);
}
