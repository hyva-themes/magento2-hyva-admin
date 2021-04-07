<?php declare(strict_types=1);

namespace Hyva\Admin\Model\TypeReflection;

class NamespaceMapper
{
    /**
     * @var NamespaceMap[]
     */
    private $memizedNamespaceMaps = [];

    public function forFile(string $file): NamespaceMap
    {
        if (! isset($this->memizedNamespaceMaps[$file])) {
            $this->memizedNamespaceMaps[$file] = NamespaceMap::forFile($file);
        }
        return $this->memizedNamespaceMaps[$file];
    }
}
