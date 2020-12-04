<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridFilter;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

class TextFilter implements ColumnDefinitionMatchingFilterInterface
{
    private LayoutInterface $layout;

    public function __construct(LayoutInterface $layout)
    {
        $this->layout = $layout;
    }

    public function isMatchingFilter(GridFilterInterface $filter): bool
    {
        // default filter type
        return true;
    }

    public function getRenderer(ColumnDefinitionInterface $columnDefinition): Template
    {
        /** @var Template $templateBlock */
        $templateBlock = $this->layout->createBlock(Template::class);
        $templateBlock->setTemplate('Hyva_Admin::filter/text.phtml');

        return $templateBlock;
    }

    public function apply(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GridFilterInterface $gridFilter,
        $filterValue
    ): void {
        if ($this->isValue($filterValue)) {
            $key = $gridFilter->getColumnDefinition()->getKey();
            $searchCriteriaBuilder->addFilter($key, '%' . $filterValue . '%', 'like');
        }
    }

    private function isValue($value): bool
    {
        return isset($value) && '' !== $value;
    }
}
