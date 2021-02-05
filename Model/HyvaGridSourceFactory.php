<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Model\GridSource\SearchCriteriaBindings;
use function array_merge as merge;
use Hyva\Admin\Model\GridSourceType\SourceTypeLocator;

use Magento\Framework\ObjectManagerInterface;

class HyvaGridSourceFactory
{
    /**
     * @var \Hyva\Admin\Model\GridSourceType\SourceTypeLocator
     */
    private $sourceTypeLocator;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager, SourceTypeLocator $sourceTypeLocator)
    {
        $this->sourceTypeLocator = $sourceTypeLocator;
        $this->objectManager     = $objectManager;
    }

    public function createFor(HyvaGridDefinitionInterface $gridDefinition): HyvaGridSourceInterface
    {
        $gridSourceConfiguration = $gridDefinition->getSourceConfig();

        if (empty($gridSourceConfiguration)) {
            $message = sprintf('No grid source configuration found for grid "%s"', $gridDefinition->getName());
            throw new \RuntimeException($message);
        }

        $sharedConstructorArguments = [
            'gridName'            => $gridDefinition->getName(),
            'sourceConfiguration' => $gridSourceConfiguration,
        ];
        $gridSourceType             = $this->objectManager->create(
            $this->sourceTypeLocator->getFor($gridDefinition->getName(), $gridSourceConfiguration),
            $sharedConstructorArguments
        );
        $bindingsConfig             = $gridSourceConfiguration['defaultSearchCriteriaBindings'] ?? [];
        $searchCriteriaBindings     = $this->objectManager->create(
            SearchCriteriaBindings::class,
            merge(['bindingsConfig' => $bindingsConfig], $sharedConstructorArguments)
        );

        $dependencies = ['gridSourceType' => $gridSourceType, 'searchCriteriaBindings' => $searchCriteriaBindings];
        return $this->objectManager->create(
            HyvaGridSourceInterface::class,
            merge($dependencies, $sharedConstructorArguments)
        );
    }
}
