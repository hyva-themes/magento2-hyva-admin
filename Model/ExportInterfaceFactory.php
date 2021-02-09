<?php
namespace Hyva\Admin\Model;

/**
 * Factory class for @see \Hyva\Admin\Model\ExportInterface
 */
class ExportInterfaceFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $_instanceName = null;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * Create class instance with specified parameters
     * @param string $instanceName
     * @param array $data
     * @return \Hyva\Admin\Model\ExportInterface
     */
    public function create(string $instanceName, array $data = [])
    {
        return $this->_objectManager->create($instanceName, $data);
    }
}
