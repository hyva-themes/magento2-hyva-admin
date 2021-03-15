<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeValueToStringConverterInterface;
use Magento\Framework\ObjectManagerInterface;

class DataTypeValueToStringConverterFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function get(string $class): DataTypeValueToStringConverterInterface
    {
        return $this->objectManager->get($class);
    }
}
