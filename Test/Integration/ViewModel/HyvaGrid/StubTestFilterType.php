<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\ViewModel\HyvaGrid;

use Hyva\Admin\Api\HyvaGridFilterTypeInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Template;

class StubTestFilterType implements HyvaGridFilterTypeInterface
{
    const STUB_OUTPUT = 'This is the output of the dummy filter type';

    public function getRenderer(ColumnDefinitionInterface $columnDefinition): Template
    {
        return new class() extends Template {
            public function __construct()
            {
                // do not call the parent constructor
            }

            public function toHtml()
            {
                return StubTestFilterType::STUB_OUTPUT;
            }
        };
    }

    public function apply(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GridFilterInterface $gridFilter,
        $filterValue
    ): void {
        // do nothing
    }
}
