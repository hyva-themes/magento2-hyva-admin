<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\ObjectManagerInterface;

class SourceModelFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    private $memoizedSourceModels = [];

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function get(string $sourceModelType): OptionSourceInterface
    {
        if (! isset($this->memoizedSourceModels[$sourceModelType])) {
            $this->memoizedSourceModels[$sourceModelType] = $this->create($sourceModelType);
        }
        return $this->memoizedSourceModels[$sourceModelType];
    }

    public function create(string $sourceModelType): OptionSourceInterface
    {
        $sourceModel = $this->instantiate($sourceModelType);
        // Ensure the returned instance implements the right interface, even if the delegate might not.
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
        // Implementing toOptionArray() is enough for source models, no interface is required.
        $sourceModel = $this->objectManager->create($sourceModelType);
        if (!method_exists($sourceModel, 'toOptionArray')) {
            throw new \RuntimeException(sprintf('Source model "%s" has no toOptionArray method', $sourceModelType));
        }
        return $sourceModel;
    }
}
