<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType\RepositorySourceType;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;

interface RepositoryGetListInterface
{
    public function __invoke(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Return the instance on which the list method will be called.
     *
     * This is used to pass the instance to processors
     *
     * @return object
     */
    public function peek();
}
