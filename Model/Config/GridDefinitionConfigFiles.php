<?php declare(strict_types=1);

namespace Hyva\Admin\Model\Config;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Module\ModuleListInterface;

use function array_filter as filter;
use function array_map as map;

class GridDefinitionConfigFiles
{
    private HyvaGridDirs $hyvaGridDirs;

    public function __construct(HyvaGridDirs $hyvaGridDirs)
    {
        $this->hyvaGridDirs = $hyvaGridDirs;
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
        }, $this->hyvaGridDirs->list());

        return filter($potentialGridConfigFiles, 'file_exists');
    }
}
