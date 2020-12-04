<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridFilter;

use Hyva\Admin\Api\GridFilterTypeInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface;

interface ColumnDefinitionMatchingFilterInterface extends GridFilterTypeInterface
{
    public function isMatchingFilter(GridFilterInterface $filter): bool;
}
