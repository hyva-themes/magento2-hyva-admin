<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSource;

use function array_map as map;
use function array_values as values;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;

class SearchCriteriaIdentity
{
    public function hash(SearchCriteriaInterface $searchCriteria): string
    {
        return sha1($this->toString($searchCriteria));
    }

    private function toString(SearchCriteriaInterface $searchCriteria): string
    {
        $criteriaArray = [
            'pageSize'    => $searchCriteria->getPageSize(),
            'currentPage' => $searchCriteria->getCurrentPage() ?? 1,
            'sortOrders'  => $this->serializeSortOrders($searchCriteria->getSortOrders()),
            'filters'     => $this->serializeFilterGroups($searchCriteria->getFilterGroups()),
        ];
        return implode('|', values($criteriaArray));
    }

    private function serializeSortOrders(?array $sortOrders): string
    {
        $a = map(function (SortOrder $order): string {
            return $order->getField() . $order->getDirection();
        }, $sortOrders ?? []);

        return implode(',', $a);
    }

    private function serializeFilterGroups(array $filterGroups): string
    {
        $a = map(function (FilterGroup $g): string {
            return $this->serializeFilters($g->getFilters());
        }, $filterGroups);

        return implode(';', $a);
    }

    private function serializeFilters(?array $filters): string
    {
        $a = map(function (Filter $f): string {
            return $f->getField() . $this->asStr($f->getValue()) . $f->getConditionType();
        }, $filters ?? []);

        return implode('_', $a);
    }

    private function asStr($v): string
    {
        return is_array($v) ? implode('.', $v) : (string) $v;
    }
}
