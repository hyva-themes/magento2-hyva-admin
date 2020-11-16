<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType\ArrayProviderSourceType;

use Hyva\Admin\Api\HyvaGridArrayProviderInterface;
use Magento\Framework\App\ObjectManager;

class ArrayProviderFactory
{
    private ObjectManager $objectManager;

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(string $arrayProviderClass): HyvaGridArrayProviderInterface
    {
        return $this->objectManager->create($arrayProviderClass);
    }
}
