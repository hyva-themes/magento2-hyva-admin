<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;

interface GridSourceTypeInterface
{
    /**
     * @return string[]
     */
    public function getColumnKeys(): array;

    /**
     * @param mixed $record
     * @param string $key
     * @return mixed
     */
    public function extractValue($record, string $key);

    public function getColumnDefinition(string $key): ColumnDefinitionInterface;

    public function fetchData();

    /**
     * @param mixed $rawGridData
     * @return mixed[]
     */
    public function extractRecords($rawGridData): array;
}
