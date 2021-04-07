<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridFilter;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\FilterOptionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\LayoutInterface;

use function array_filter as filter;
use function array_map as map;
use function array_values as values;

class SelectFilter implements ColumnDefinitionMatchingFilterInterface
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
        return (bool) $filter->getOptions();
    }

    public function getRenderer(ColumnDefinitionInterface $columnDefinition): Template
    {
        /** @var Template $templateBlock */
        $templateBlock = $this->layout->createBlock(Template::class);
        $templateBlock->setTemplate('Hyva_Admin::grid/filter/select.phtml');

        return $templateBlock;
    }

    public function apply(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GridFilterInterface $gridFilter,
        $filterValue
    ): void {
        if ($option = $this->getSelectedOption($gridFilter->getOptions(), $filterValue)) {
            $key     = $gridFilter->getColumnDefinition()->getKey();
            $filters = map(function ($value) use ($key): Filter {
                return $this->buildSelectValueFilter($key, $value);
            }, $option->getValues());

            $searchCriteriaBuilder->addFilters($filters);
        }
    }

    private function getSelectedOption(array $options, $value): ?FilterOptionInterface
    {
        return values(filter($options, function (FilterOptionInterface $option) use ($value): bool {
                return $option->getValueId() === $value;
            }))[0] ?? null;
    }

    private function buildSelectValueFilter(string $key, $value): Filter
    {
        return new Filter([
            Filter::KEY_FIELD          => $key,
            Filter::KEY_VALUE          => $value,
            Filter::KEY_CONDITION_TYPE => 'finset',
        ]);
    }
}
