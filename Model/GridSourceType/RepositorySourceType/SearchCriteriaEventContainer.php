<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType\RepositorySourceType;

use Magento\Framework\Api\SearchCriteriaInterface;

class SearchCriteriaEventContainer
{
    /**
     * @var SearchCriteriaInterface
     */
    private $searchCriteria;

    public function __construct(SearchCriteriaInterface $searchCriteria)
    {
        $this->searchCriteria = $searchCriteria;
    }

    public function replaceSearchCriteria(SearchCriteriaInterface $searchCriteria): void
    {
        $this->searchCriteria = $searchCriteria;
    }

    public function getSearchCriteria(): SearchCriteriaInterface
    {
        return $this->searchCriteria;
    }
}
