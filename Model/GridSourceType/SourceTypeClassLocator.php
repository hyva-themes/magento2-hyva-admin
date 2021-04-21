<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

class SourceTypeClassLocator
{
    private $typeToGridSourceClassMap = [
        'repository' => RepositoryGridSourceType::class,
        'collection' => CollectionGridSourceType::class,
        'query'      => QueryGridSourceType::class,
        'array'      => ArrayProviderGridSourceType::class,
    ];

    public function getFor(string $gridName, array $gridSourceConfiguration): string
    {
        $sourceType = $this->getType($gridSourceConfiguration);
        if (! $sourceType) {
            $msg = sprintf('No source type configuration found for grid "%s"', $gridName);
            throw new \RuntimeException($msg);
        }
        $class      = $this->typeToGridSourceClassMap[$sourceType] ?? '';
        if (!$class) {
            $msg = sprintf('Unknown HyvaGrid source type on grid "%s": "%s"', $gridName, $sourceType);
            throw new \OutOfBoundsException($msg);
        }
        return $class;
    }

    private function getType(array $gridSourceConfiguration): string
    {
        if (isset($gridSourceConfiguration['@type'])) {
            return $gridSourceConfiguration['@type'];
        }
        if (isset($gridSourceConfiguration['repositoryListMethod'])) {
            return 'repository';
        }
        if (isset($gridSourceConfiguration['collection'])) {
            return 'collection';
        }
        if (isset($gridSourceConfiguration['query'])) {
            return 'query';
        }
        if (isset($gridSourceConfiguration['arrayProvider'])) {
            return 'array';
        }

        return '';
    }
}
