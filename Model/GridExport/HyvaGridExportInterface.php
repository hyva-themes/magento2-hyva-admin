<?php

namespace Hyva\Admin\Model\GridExport;

use Magento\Framework\Api\SearchCriteriaInterface;

interface HyvaGridExportInterface
{

    public function getSearchCriteria() : SearchCriteriaInterface;

    public function getRowsForSearchCriteria(SearchCriteriaInterface $searchCriteria): array;

    public function getTotalRowsCount(): int;
}