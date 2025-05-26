<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridFilter;

use Hyva\Admin\Model\DataType\BooleanDataType;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

class BooleanFilter implements ColumnDefinitionMatchingFilterInterface
{
    /**
     * @var LayoutInterface
     */
    private $layout;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    public function __construct(
        LayoutInterface $layout,
        FilterBuilder $filterBuilder,
    ) {
        $this->layout = $layout;
        $this->filterBuilder = $filterBuilder;
    }

    public function isMatchingFilter(GridFilterInterface $filter): bool
    {
        return $filter->getColumnDefinition()->getType() === BooleanDataType::TYPE_BOOL;
    }

    public function getRenderer(ColumnDefinitionInterface $columnDefinition): Template
    {
        /** @var Template $templateBlock */
        $templateBlock = $this->layout->createBlock(Template::class);
        $templateBlock->setTemplate('Hyva_Admin::grid/filter/bool.phtml');

        return $templateBlock;
    }

    public function apply(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GridFilterInterface $gridFilter,
        $filterValue
    ): void {
        if ($this->isValue($filterValue)) {
            $key = $gridFilter->getColumnDefinition()->getKey();
            if ((int) ($filterValue > 0)) {
                $searchCriteriaBuilder->addFilter($key, (int) $filterValue, 'eq');
            } else {
                $integerFilter = $this->filterBuilder
                    ->setField($key)
                    ->setValue((int) $filterValue)
                    ->create();
                $nullFilter = $this->filterBuilder
                    ->setField($key)
                    ->setValue(true)
                    ->setConditionType('null')
                    ->create();
                $searchCriteriaBuilder->addFilters([$integerFilter, $nullFilter]);
            }
        }
    }

    private function isValue($value): bool
    {
        return isset($value) && '' !== $value;
    }
}
