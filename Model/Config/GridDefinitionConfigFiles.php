<?php declare(strict_types=1);

namespace Hyva\Admin\Model\Config;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Module\ModuleListInterface;

use function array_filter as filter;
use function array_map as map;

class GridDefinitionConfigFiles
{
    private ModuleListInterface $moduleList;

    private ComponentRegistrarInterface $componentRegistrar;

    public function __construct(ModuleListInterface $moduleList, ComponentRegistrarInterface $componentRegistrar)
    {
        $this->componentRegistrar = $componentRegistrar;
        $this->moduleList         = $moduleList;
    }

    private function getHyvaGridDirName(string $module): string
    {
        return $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $module) . '/view/adminhtml/hyva-grid';
    }

    private function getActiveModules(): array
    {
        return $this->moduleList->getNames();
    }

    private function getHyvaGridDirs(): array
    {
        $potentialHyvaGridDirs = map([$this, 'getHyvaGridDirName'], $this->getActiveModules());

        return filter($potentialHyvaGridDirs, 'is_dir');
    }

    private function buildGridDefinitionFileName(string $gridName, string $dir): string
    {
        $secureGridName = str_replace('/', '', $gridName);

        return $dir . '/' . $secureGridName . '.xml';
    }

    /**
     * @param string $gridName
     * @return string[]
     */
    public function getGridDefinitionFiles(string $gridName): array
    {
        $potentialGridConfigFiles = map(function (string $dir) use ($gridName): string {
            return $this->buildGridDefinitionFileName($gridName, $dir);
        }, $this->getHyvaGridDirs());

        return filter($potentialGridConfigFiles, 'file_exists');
    }
}
