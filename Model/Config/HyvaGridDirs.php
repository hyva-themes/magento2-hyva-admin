<?php declare(strict_types=1);

namespace Hyva\Admin\Model\Config;

use Magento\Framework\App\State as AppState;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Module\ModuleListInterface;

class HyvaGridDirs extends HyvaConfigDirs
{
    public function __construct(
        ModuleListInterface $moduleList,
        ComponentRegistrarInterface $componentRegistrar,
        AppState $appState
    ) {
        parent::__construct($moduleList, $componentRegistrar, $appState, /* configDirName */ 'hyva-grid');
    }
}
