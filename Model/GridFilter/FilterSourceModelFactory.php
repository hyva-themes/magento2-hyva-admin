<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridFilter;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\ObjectManagerInterface;

class FilterSourceModelFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(string $sourceModelType): OptionSourceInterface
    {
        $sourceModel = $this->instantiate($sourceModelType);
        return new class($sourceModel) implements OptionSourceInterface {
            private $sourceModel;

            public function __construct($sourceModel)
            {
                $this->sourceModel = $sourceModel;
            }

            public function toOptionArray()
            {
                return $this->sourceModel->toOptionArray();
            }
        };
    }

    private function instantiate(string $sourceModelType)
    {
        $sourceModel = $this->objectManager->create($sourceModelType);
        if (!method_exists($sourceModel, 'toOptionArray')) {
            throw new \RuntimeException();
        }
        return $sourceModel;
    }
}
