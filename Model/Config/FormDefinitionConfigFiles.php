<?php declare(strict_types=1);

namespace Hyva\Admin\Model\Config;

class FormDefinitionConfigFiles extends DefinitionConfigFiles
{
    public function __construct(HyvaFormDirs $hyvaConfigDirs)
    {
        parent::__construct($hyvaConfigDirs);
    }
}
