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
    /**
     * @var ModuleListInterface
     */
    private $moduleList;

    /**
     * @var ComponentRegistrarInterface
     */
    private $componentRegistrar;

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var string
     */
    private $configDirName;

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
        return map(function (string $area) use ($viewDirStringTemplate): string {
            return sprintf($viewDirStringTemplate, $area);
        }, $areaDirs);
    }
}
