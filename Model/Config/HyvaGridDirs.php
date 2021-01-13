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

class HyvaGridDirs
{
    /**
     * @var \Magento\Framework\Module\ModuleListInterface
     */
    private $moduleList;

    /**
     * @var \Magento\Framework\Component\ComponentRegistrarInterface
     */
    private $componentRegistrar;

    /**
     * @var AppState
     */
    private $appState;

    public function __construct(
        ModuleListInterface $moduleList,
        ComponentRegistrarInterface $componentRegistrar,
        AppState $appState
    ) {
        $this->moduleList         = $moduleList;
        $this->componentRegistrar = $componentRegistrar;
        $this->appState           = $appState;
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
        $potentialHyvaGridDirs = merge(...map([$this, 'getHyvaGridDirNames'], $this->getActiveModules()));

        return values(filter($potentialHyvaGridDirs, 'is_dir'));
    }

    private function getHyvaGridDirNames(string $module): array
    {
        $areaDirs = ['base', $this->appState->getAreaCode()];
        return map(function (string $area) use ($module) : string {
            return $this->moduleDir($module) . '/view/' . $area . '/hyva-grid';
        }, $areaDirs);
    }
}
