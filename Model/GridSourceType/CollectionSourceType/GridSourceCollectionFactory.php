<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType\CollectionSourceType;

use Magento\Framework\Data\Collection\AbstractDb as AbstractDbCollection;
use Magento\Framework\ObjectManagerInterface;

class GridSourceCollectionFactory
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function create(string $collectionClass): AbstractDbCollection
    {
        return $this->objectManager->create($collectionClass);
    }
}
