<?php
namespace Hyva\Admin\Model\GridExport;

use Magento\Framework\ObjectManagerInterface;

/**
 * Factory class for @see \Hyva\Admin\Model\TypeInterface
 */
class TypeInterfaceFactory
{
    private ObjectManagerInterface $_objectManager;

    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * Create class instance with specified parameters
     * @param string $instanceName
     * @param array  $data
     * @return TypeInterface
     */
    public function create(string $instanceName, array $data = []) : TypeInterface
    {
        return $this->_objectManager->create($instanceName, $data);
    }
}
