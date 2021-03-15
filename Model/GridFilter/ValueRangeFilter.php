<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridFilter;

use Hyva\Admin\Model\DataType\IntDataType;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

class ValueRangeFilter implements ColumnDefinitionMatchingFilterInterface
{
    /**
     * @var LayoutInterface
     */
    private $layout;

    public function __construct(LayoutInterface $layout)
    {
        $this->layout = $layout;
    }

    public function isMatchingFilter(GridFilterInterface $filter): bool
    {
        return $filter->getColumnDefinition()->getType() === IntDataType::TYPE_INT;
    }

    public function getRenderer(ColumnDefinitionInterface $columnDefinition): Template
    {
        /** @var Template $templateBlock */
        $templateBlock = $this->layout->createBlock(Template::class);
        $templateBlock->setTemplate('Hyva_Admin::grid/filter/value-range.phtml');

        return $templateBlock;
    }

    public function apply(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GridFilterInterface $gridFilter,
        $filterValue
    ): void {
        $key = $gridFilter->getColumnDefinition()->getKey();
        if ($this->isValue($from = $filterValue['from'] ?? '')) {
            $searchCriteriaBuilder->addFilter($key, $from, 'gteq');
        }
        if ($this->isValue($to = $filterValue['to'] ?? '')) {
            $searchCriteriaBuilder->addFilter($key, $to, 'lteq');
        }
    }

    private function isValue($value): bool
    {
        return isset($value) && '' !== $value;
    }
}
