<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType\RepositorySourceType;

class HyvaAdminEventContainer
{
    private $hyvaAdminEventObject;

    public function __construct($hyvaAdminEventObject)
    {
        $this->hyvaAdminEventObject = $hyvaAdminEventObject;
    }

    public function replaceContainerData($hyvaAdminEventObject): void
    {
        $this->hyvaAdminEventObject = $hyvaAdminEventObject;
    }

    public function getContainerData()
    {
        return $this->hyvaAdminEventObject;
    }
}
