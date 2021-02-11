<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface HyvaGridSourceInterface
{
    /**
     * @param ColumnDefinitionInterface[] $configuredColumns
     * @param string[] $hiddenKeys
     * @param bool $keepAll
     * @return ColumnDefinitionInterface[]
     */
    public function extractColumnDefinitions(array $configuredColumns, array $hiddenKeys, bool $keepAll): array;

    public function getRecords(SearchCriteriaInterface $searchCriteria): array;

    public function extractValue($record, string $key);

    public function getTotalCount(SearchCriteriaInterface $searchCriteria): int;
}
