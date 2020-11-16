<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use Hyva\Admin\Model\RawGridSourceContainer;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;

class RepositoryGridSourceType implements GridSourceTypeInterface
{
    public function getColumnKeys(): array
    {
        return [];
    }

    public function extractValue($record, string $key)
    {

    }

    public function getColumnDefinition(string $key): ColumnDefinitionInterface
    {

    }

    public function fetchData(): RawGridSourceContainer
    {

    }

    public function extractRecords(RawGridSourceContainer $rawGridData): array
    {

    }
}
