<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface HyvaGridSourceInterface
{
    /**
     * @param ColumnDefinitionInterface[] $includedColumns
     * @return ColumnDefinitionInterface[]
     */
    public function extractColumnDefinitions(array $includedColumns, bool $keepAllSourceCols): array;

    public function getRecords(SearchCriteriaInterface $searchCriteria): array;

    public function extractValue($record, string $key);

    public function getTotalCount(SearchCriteriaInterface $searchCriteria): int;
}
