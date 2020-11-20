<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType\RepositorySourceType;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface RepositoryGetListInterface
{
    public function __invoke(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;
}
