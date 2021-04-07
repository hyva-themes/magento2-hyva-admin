<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType\RepositorySourceType;

class HyvaGridEventContainer
{
    /**
     * @var mixed
     */
    private $hyvaGridEventObject;

    public function __construct($hyvaGridEventObject)
    {
        $this->hyvaGridEventObject = $hyvaGridEventObject;
    }

    public function replaceContainerData($hyvaAdminEventObject): void
    {
        $this->hyvaGridEventObject = $hyvaAdminEventObject;
    }

    public function getContainerData()
    {
        return $this->hyvaGridEventObject;
    }
}
