<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSource;

use Hyva\Admin\Model\MethodValueBindings;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;

use function array_reduce as reduce;

class SearchCriteriaBindings
{
    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    private $filterGroupBuilder;

    /**
     * @var MethodValueBindings
     */
    private $methodValueBindings;

    /**
     * @var array[]
     */
    private $bindingsConfig;

    /**
     * @var string
     */
    private $combineConditionsWith;

    public function __construct(
        MethodValueBindings $methodValueBindings,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        string $combineConditionsWith = 'and',
        array $bindingsConfig = []
    ) {
        $this->methodValueBindings   = $methodValueBindings;
        $this->filterBuilder         = $filterBuilder;
        $this->filterGroupBuilder    = $filterGroupBuilder;
        $this->bindingsConfig        = $bindingsConfig;
        $this->combineConditionsWith = $combineConditionsWith;
    }

    public function apply(SearchCriteriaInterface $searchCriteria): SearchCriteriaInterface
    {
        return 'and' === $this->combineConditionsWith
            ? $this->applyAllHaveToMatch($searchCriteria)
            : $this->applyAsAlternatives($searchCriteria);
    }

    private function applyAllHaveToMatch(SearchCriteriaInterface $searchCriteria): SearchCriteriaInterface
    {
        $initialFilterGroups   = $searchCriteria->getFilterGroups();
        $processedFilterGroups = reduce($this->bindingsConfig, [$this, 'applyBindingUsingAnd'], $initialFilterGroups);

        return $searchCriteria->setFilterGroups($processedFilterGroups);
    }

    private function applyBindingUsingAnd(array $filterGroups, array $binding): array
    {
        $filterGroups[] = $this->filterGroupBuilder->addFilter($this->createField($binding))->create();

        return $filterGroups;
    }

    private function applyAsAlternatives(SearchCriteriaInterface $searchCriteria): SearchCriteriaInterface
    {
        $filterGroups   = $searchCriteria->getFilterGroups();
        $filterGroups[] = reduce($this->bindingsConfig, [$this, 'applyBindingUsingOr'], $this->filterGroupBuilder)->create();

        return $searchCriteria->setFilterGroups($filterGroups);
    }

    private function applyBindingUsingOr(FilterGroupBuilder $filterGroupBuilder, array $binding): FilterGroupBuilder
    {
        $filterGroupBuilder->addFilter($this->createField($binding));

        return $filterGroupBuilder;
    }

    private function createField(array $binding): Filter
    {
        return $this->filterBuilder->setField($binding['field'] ?? '')
                                   ->setValue($this->methodValueBindings->resolveBindValue($binding))
                                   ->setConditionType($binding['condition'] ?? 'eq')
                                   ->create();
    }
}
