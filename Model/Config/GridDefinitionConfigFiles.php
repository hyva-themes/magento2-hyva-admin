<?php declare(strict_types=1);

namespace Hyva\Admin\Model\Config;

class GridDefinitionConfigFiles extends DefinitionConfigFiles
{
    public function __construct(HyvaGridDirs $hyvaConfigDirs)
    {
        parent::__construct($hyvaConfigDirs);
    }

}
