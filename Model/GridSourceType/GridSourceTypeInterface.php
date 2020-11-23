<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use Hyva\Admin\Model\RawGridSourceContainer;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;

interface GridSourceTypeInterface
{
    /**
     * @return string[]
     */
    public function getColumnKeys(): array;

    public function getColumnDefinition(string $key): ColumnDefinitionInterface;

    // todo: receive paging and filtering data
    public function fetchData(): RawGridSourceContainer;

    /**
     * @param RawGridSourceContainer $rawGridData
     * @return mixed[]
     */
    public function extractRecords(RawGridSourceContainer $rawGridData): array;

    /**
     * @param mixed $record
     * @param string $key
     * @return mixed
     */
    public function extractValue($record, string $key);
}
