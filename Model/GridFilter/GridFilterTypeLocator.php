<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridFilter;

use Hyva\Admin\Api\HyvaGridFilterTypeInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface;
use Magento\Framework\ObjectManagerInterface;

class GridFilterTypeLocator
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var array
     */
    private $columnTypeMatchingFilterTypeMap;

    public function __construct(ObjectManagerInterface $objectManager, array $columnTypeMatchingFilterMap)
    {
        $this->objectManager = $objectManager;
        $this->columnTypeMatchingFilterTypeMap = $columnTypeMatchingFilterMap;
    }

    public function findFilterForColumn(
        GridFilterInterface $gridFilter,
        ColumnDefinitionInterface $columnDefinition
    ): HyvaGridFilterTypeInterface {
        foreach ($this->columnTypeMatchingFilterTypeMap as $type) {
            $filterType = $this->get($type);
            if ($this->canMatchColumn($filterType) && $filterType->isMatchingFilter($gridFilter, $columnDefinition)) {
                return $filterType;
            }
        }

        $msg = sprintf('Unable to determine filter type for column "%s"', $columnDefinition->getKey());
        throw new \OutOfBoundsException($msg);
    }

    private function canMatchColumn(HyvaGridFilterTypeInterface $filterType): bool
    {
        return $filterType instanceof ColumnDefinitionMatchingFilterInterface;
    }

    public function get(string $filterType): HyvaGridFilterTypeInterface
    {
        return $this->objectManager->get($filterType);
    }
}
