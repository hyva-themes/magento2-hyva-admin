<?php
namespace Hyva\Admin\Model;

use Magento\Framework\ObjectManagerInterface;

/**
 * Factory class for @see \Hyva\Admin\Model\ExportInterface
 */
class ExportInterfaceFactory
{
    protected ObjectManagerInterface $_objectManager;

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
     * @return ExportInterface
     */
    public function create(string $instanceName, array $data = []) : ExportInterface
    {
        return $this->_objectManager->create($instanceName, $data);
    }
}
