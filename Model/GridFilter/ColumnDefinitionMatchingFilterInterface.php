<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridFilter;

use Hyva\Admin\Api\HyvaGridFilterTypeInterface;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface;

interface ColumnDefinitionMatchingFilterInterface extends HyvaGridFilterTypeInterface
{
    public function isMatchingFilter(GridFilterInterface $filter): bool;
}
