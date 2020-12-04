<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridFilter;

use Hyva\Admin\Api\GridFilterTypeInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface;
use Magento\Framework\ObjectManagerInterface;

class GridFilterTypeLocator
{
    private ObjectManagerInterface $objectManager;

    private array $columnTypeMatchingFilterTypeMap;

    public function __construct(ObjectManagerInterface $objectManager, array $columnTypeMatchingFilterMap)
    {
        $this->objectManager = $objectManager;
        $this->columnTypeMatchingFilterTypeMap = $columnTypeMatchingFilterMap;
    }

    public function findFilterForColumn(
        GridFilterInterface $gridFilter,
        ColumnDefinitionInterface $columnDefinition
    ): GridFilterTypeInterface {
        foreach ($this->columnTypeMatchingFilterTypeMap as $type) {
            $filterType = $this->get($type);
            if ($this->canMatchColumn($filterType) && $filterType->isMatchingFilter($gridFilter, $columnDefinition)) {
                return $filterType;
            }
        }

        $msg = sprintf('Unable to determine filter type for column "%s"', $columnDefinition->getKey());
        throw new \OutOfBoundsException($msg);
    }

    private function canMatchColumn(GridFilterTypeInterface $filterType): bool
    {
        return $filterType instanceof ColumnDefinitionMatchingFilterInterface;
    }

    public function get(string $filterType): GridFilterTypeInterface
    {
        return $this->objectManager->get($filterType);
    }
}
