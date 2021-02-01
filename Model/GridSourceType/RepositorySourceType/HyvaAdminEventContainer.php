<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType\RepositorySourceType;

class HyvaAdminEventContainer
{
    private $hyvaAdminContainer;

    public function __construct($hyvaAdminContainer)
    {
        $this->hyvaAdminContainer = $hyvaAdminContainer;
    }

    public function replaceContainerData($hyvaAdminContainer): void
    {
        $this->hyvaAdminContainer = $hyvaAdminContainer;
    }

    public function getContainerData()
    {
        return $this->hyvaAdminContainer;
    }
}
