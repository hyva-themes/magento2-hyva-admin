<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSource;

use function array_column as pick;
use function array_filter as filter;
use function array_map as map;
use function array_values as values;

use Hyva\Admin\Api\HyvaGridSourceProcessorInterface;
use Magento\Framework\ObjectManagerInterface;

class GridSourceProcessorBuilder
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array[] $processorsConfig
     * @return HyvaGridSourceProcessorInterface[]
     */
    public function build(array $processorsConfig): array
    {
        $activeProcessors = pick(filter($processorsConfig, function (array $processorConfig) {
            return ($processorConfig['enabled'] ?? 'true') !== 'false';
        }), 'class');
        return values(map([$this, 'createProcessor'], $activeProcessors));
    }

    private function createProcessor(string $type): HyvaGridSourceProcessorInterface
    {
        return $this->objectManager->create($type);
    }
}
