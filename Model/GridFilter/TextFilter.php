<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridFilter;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

class TextFilter implements ColumnDefinitionMatchingFilterInterface
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
        // default filter type
        return true;
    }

    public function getRenderer(ColumnDefinitionInterface $columnDefinition): Template
    {
        /** @var Template $templateBlock */
        $templateBlock = $this->layout->createBlock(Template::class);
        $templateBlock->setTemplate('Hyva_Admin::grid/filter/text.phtml');

        return $templateBlock;
    }

    public function apply(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GridFilterInterface $gridFilter,
        $filterValue
    ): void {
        if ($this->isValue($filterValue)) {
            $key = $gridFilter->getColumnDefinition()->getKey();
            // TODO: improve escaping of LIKE expression wildcards to avoid double escaping
            // E.g. \% should becode \\\% (not as in the current implementation \\%)
            $escapedFilterValue = str_replace(['%', '_'], ['\%', '\_'], $filterValue);
            $searchCriteriaBuilder->addFilter($key, '%' . $escapedFilterValue . '%', 'like');
        }
    }

    private function isValue($value): bool
    {
        return isset($value) && '' !== $value;
    }
}
