<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridFilter;

use Hyva\Admin\Model\DataType\DateDataType;
use Hyva\Admin\Model\DataType\DateTimeDataType;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

class DateRangeFilter implements ColumnDefinitionMatchingFilterInterface
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
        return in_array(
            $filter->getColumnDefinition()->getType(),
            [DateTimeDataType::TYPE_DATETIME, DateDataType::TYPE_DATE],
            true
        );
    }

    public function getRenderer(ColumnDefinitionInterface $columnDefinition): Template
    {
        /** @var Template $templateBlock */
        $templateBlock = $this->layout->createBlock(Template::class);
        $templateBlock->setTemplate('Hyva_Admin::grid/filter/date-range.phtml');

        return $templateBlock;
    }

    public function apply(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GridFilterInterface $gridFilter,
        $filterValue
    ): void {
        $key = $gridFilter->getColumnDefinition()->getKey();
        if ($this->isValue($from = $filterValue['from'] ?? '')) {
            $searchCriteriaBuilder->addFilter($key, $from, 'from');
        }
        if ($this->isValue($to = $filterValue['to'] ?? '')) {
            $searchCriteriaBuilder->addFilter($key, $to, 'to');
        }
    }

    private function isValue($value): bool
    {
        return isset($value) && '' !== $value;
    }
}
