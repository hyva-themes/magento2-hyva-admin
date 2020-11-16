<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Model\GridSourceType\SourceTypeLocator;
use Magento\Framework\ObjectManagerInterface;

class HyvaGridSourceFactory
{
    private SourceTypeLocator $sourceTypeLocator;

    private ObjectManagerInterface $objectManager;

    public function __construct(ObjectManagerInterface $objectManager, SourceTypeLocator $sourceTypeLocator)
    {
        $this->sourceTypeLocator = $sourceTypeLocator;
        $this->objectManager = $objectManager;
    }

    public function createFor(array $gridSourceConfiguration): HyvaGridSourceInterface
    {
        $gridSourceType = $this->objectManager->create(
            $this->sourceTypeLocator->getFor($gridSourceConfiguration),
            ['sourceConfiguration' => $gridSourceConfiguration]
        );
        return $this->objectManager->create(HyvaGridSourceInterface::class, ['gridSourceType' => $gridSourceType]);
    }
}
