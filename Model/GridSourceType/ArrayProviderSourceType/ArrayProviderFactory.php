<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType\ArrayProviderSourceType;

use Hyva\Admin\Api\HyvaGridArrayProviderInterface;
use Magento\Framework\ObjectManagerInterface;

class ArrayProviderFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(string $arrayProviderClass): HyvaGridArrayProviderInterface
    {
        return $this->objectManager->create($arrayProviderClass);
    }
}
