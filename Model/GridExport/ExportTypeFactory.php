<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridExport;

use Magento\Framework\ObjectManagerInterface;

class ExportTypeFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(string $instanceName, array $data = []) : ExportTypeInterface
    {
        return $this->objectManager->create($instanceName, $data);
    }
}
