<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use Hyva\Admin\Model\RawGridSourceContainer;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface GridSourceTypeInterface
{
    /**
     * @return string[]
     */
    public function getColumnKeys(): array;

    public function getColumnDefinition(string $key): ColumnDefinitionInterface;

    public function fetchData(SearchCriteriaInterface $searchCriteria): RawGridSourceContainer;

    public function getRecordType(): string;

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

    public function extractTotalRowCount(RawGridSourceContainer $rawGridData): int;

    public function getGridName(): string;
}
