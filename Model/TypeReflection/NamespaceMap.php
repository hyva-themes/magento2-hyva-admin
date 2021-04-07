<?php declare(strict_types=1);

namespace Hyva\Admin\Model\TypeReflection;

use function array_merge as merge;

class NamespaceMap
{
    /**
     * @var string[]
     */
    private $namespaceMap;

    /**
     * @var string
     */
    private $file;

    private function __construct(string $file, array $namespaceMap)
    {
        $this->file         = $file;
        $this->namespaceMap = $namespaceMap;
    }

    /**
     * Simplified namespace resolution
     *
     * Only supports single line imports and aliases.
     */
    public static function forFile(string $fileName): NamespaceMap
    {
        $f = fopen($fileName, 'r');
        try {
            $map = self::readUntilCassDefiniton($f);
        } finally {
            fclose($f);
        }
        return new self($fileName, $map ?? []);
    }

    private static function readUntilCassDefiniton($f): array
    {
        $map = [];
        while (!feof($f)) {
            if (!is_string($line = fgets($f, 4096))) {
                break;
            }
            if (!isset($map['@default']) && preg_match('/namespace +([^;[:space:]]+)/', $line, $m)) {
                $map['@default'] = $m[1];
            } elseif (preg_match('/use +(?<import>[^;[:space:]]+)(?:\s+as\s+(?<alias>[^;[:space:]]+))?/', $line, $m)) {
                $fullyQualifiedClass = ltrim($m['import'], '\\');
                $import              = $m['alias'] ?? self::lastPart($fullyQualifiedClass);
                $map[$import]        = $fullyQualifiedClass;
            } elseif (preg_match('/^(?:class|interface) /', trim($line))) {
                break;
            }
        }
        return $map;
    }

    private static function lastPart(string $fqcn): string
    {
        $parts = explode('\\', $fqcn);
        return end($parts);
    }

    public function qualify(string $class): string
    {
        if (substr($class, 0, 1) === '\\') {
            return $class;
        }
        if (isset($this->namespaceMap[$class])) {
            return $this->namespaceMap[$class];
        }
        [$base, $rest] = merge(explode('\\', $class, 2), [null]);
        return $base === $class
            ? ($this->namespaceMap['@default'] ?? '') . '\\' . $class
            : $this->qualify($base) . '\\' . $rest;
    }

    public function getFile(): string
    {
        return $this->file;
    }
}
