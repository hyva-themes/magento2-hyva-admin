<?php declare(strict_types=1);

namespace Hyva\Admin\Model\Config;

use function array_filter as filter;
use function array_map as map;

abstract class DefinitionConfigFiles
{
    /**
     * @var HyvaConfigDirs
     */
    private $hyvaConfigDirs;

    public function __construct(HyvaConfigDirs $hyvaConfigDirs)
    {
        $this->hyvaConfigDirs = $hyvaConfigDirs;
    }

    private function buildDefinitionFileName(string $gridName, string $dir): string
    {
        $secureGridName = str_replace('/', '', $gridName);

        return $dir . '/' . $secureGridName . '.xml';
    }

    /**
     * @param string $configFileName
     * @return string[]
     */
    public function getConfigDefinitionFiles(string $configFileName): array
    {
        $potentialGridConfigFiles = map(function (string $dir) use ($configFileName): string {
            return $this->buildDefinitionFileName($configFileName, $dir);
        }, $this->hyvaConfigDirs->list());

        return filter($potentialGridConfigFiles, 'file_exists');
    }
}
