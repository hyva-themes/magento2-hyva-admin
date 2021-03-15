<?php declare(strict_types=1);

namespace Hyva\Admin\Model\DataType;

use Hyva\Admin\Api\DataTypeGuesserInterface;
use Magento\Framework\ObjectManagerInterface;

class DataTypeGuesserFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function get(string $class): ?DataTypeGuesserInterface
    {
        $instance = $this->objectManager->get($class);

        return $instance instanceof DataTypeGuesserInterface
            ? $instance
            : null;
    }
}
