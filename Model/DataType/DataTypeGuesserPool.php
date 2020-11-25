<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeGuesserInterface;
use Magento\Framework\ObjectManagerInterface;

class DataTypeGuesserPool
{
    private ObjectManagerInterface $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function get(string $class): DataTypeGuesserInterface
    {
        return $this->objectManager->get($class);
    }
}
