<?php declare(strict_types=1);

namespace Hyva\Admin\Api;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Template;

interface HyvaGridFilterTypeInterface
{
    public function getRenderer(ColumnDefinitionInterface $columnDefinition): Template;

    public function apply(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GridFilterInterface $gridFilter,
        $filterValue
    ): void;
}
