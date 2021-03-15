<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSource;

use Hyva\Admin\Model\MethodValueBindings;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\ObjectManagerInterface;

use function array_reduce as reduce;

class SearchCriteriaBindings
{
    private ObjectManagerInterface $objectManager;

    private FilterBuilder $filterBuilder;

    private FilterGroupBuilder $filterGroupBuilder;

    private MethodValueBindings $methodValueBindings;

    private array $bindingsConfig;

    public function __construct(
        ObjectManagerInterface $objectManager,
        MethodValueBindings $methodValueBindings,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        array $bindingsConfig = []
    ) {
        $this->objectManager       = $objectManager;
        $this->methodValueBindings = $methodValueBindings;
        $this->filterBuilder       = $filterBuilder;
        $this->filterGroupBuilder  = $filterGroupBuilder;
        $this->bindingsConfig      = $bindingsConfig;
    }

    public function apply(SearchCriteriaInterface $searchCriteria): SearchCriteriaInterface
    {
        $initialFilterGroups   = $searchCriteria->getFilterGroups();
        $processedFilterGroups = reduce($this->bindingsConfig, [$this, 'applyBinding'], $initialFilterGroups);

        return $searchCriteria->setFilterGroups($processedFilterGroups);
    }

    private function applyBinding(array $filterGroups, array $binding): array
    {
        $filter = $this->filterBuilder->setField($binding['field'] ?? '')
                                      ->setValue($this->methodValueBindings->resolveBindValue($binding))
                                      ->setConditionType($binding['condition'] ?? 'eq')
                                      ->create();

        $filterGroups[] = $this->filterGroupBuilder->addFilter($filter)->create();

        return $filterGroups;
    }
}
