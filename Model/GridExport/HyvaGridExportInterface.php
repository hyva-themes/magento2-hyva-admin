<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridExport;

use Hyva\Admin\ViewModel\HyvaGrid\GridExportInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface HyvaGridExportInterface
{
    public function getSearchCriteria(): SearchCriteriaInterface;

    public function getRowsForSearchCriteria(SearchCriteriaInterface $searchCriteria): array;

    public function getTotalRowsCount(): int;

    public function getExport(string $exportType): ?GridExportInterface;

    public function getGridName(): string;
}
