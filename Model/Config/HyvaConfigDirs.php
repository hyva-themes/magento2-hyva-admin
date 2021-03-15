<?php declare(strict_types=1);

namespace Hyva\Admin\Model\Config;

use Magento\Framework\App\State as AppState;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Module\ModuleListInterface;

use function array_filter as filter;
use function array_map as map;
use function array_merge as merge;
use function array_values as values;

abstract class HyvaConfigDirs
{
    private ModuleListInterface $moduleList;

    private ComponentRegistrarInterface $componentRegistrar;

    private AppState $appState;

    private string $configDirName;

    public function __construct(
        ModuleListInterface $moduleList,
        ComponentRegistrarInterface $componentRegistrar,
        AppState $appState,
        string $configDirName
    ) {
        $this->moduleList         = $moduleList;
        $this->componentRegistrar = $componentRegistrar;
        $this->appState           = $appState;
        $this->configDirName      = $configDirName;
    }

    private function moduleDir(string $module): string
    {
        return $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $module);
    }

    private function getActiveModules(): array
    {
        return $this->moduleList->getNames();
    }

    public function list(): array
    {
        $potentialHyvaGridDirs = merge(...map([$this, 'getHyvaConfigDirNames'], $this->getActiveModules()));

        return values(filter($potentialHyvaGridDirs, 'is_dir'));
    }

    private function getHyvaConfigDirNames(string $module): array
    {
        $areaDirs              = ['base', $this->appState->getAreaCode()];
        $viewDirStringTemplate = $this->moduleDir($module) . '/view/%s/' . $this->configDirName;
        return map(fn(string $area): string => sprintf($viewDirStringTemplate, $area), $areaDirs);
    }
}
