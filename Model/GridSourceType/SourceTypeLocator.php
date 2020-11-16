<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

class SourceTypeLocator
{
    private array $typeToGridSourceClassMap = [
        'repository' => RepositoryGridSourceType::class,
        'collection' => CollectionGridSourceType::class,
        'query'      => QueryGridSourceType::class,
    ];

    public function getFor(array $gridSourceConfiguration): string
    {
        $sourceType = $this->getType($gridSourceConfiguration);
        $class      = $this->typeToGridSourceClassMap[$sourceType] ?? '';
        if (!$class) {
            throw new \OutOfBoundsException(sprintf('Unknown HyvaGrid source type: "%s"', $sourceType));
        }
        return $class;
    }

    private function getType(array $gridSourceConfiguration): string
    {
        if (isset($gridSourceConfiguration['@type'])) {
            return $gridSourceConfiguration['@type'];
        }
        if (isset($gridSourceConfiguration['repository'])) {
            return 'repository';
        }
        if (isset($gridSourceConfiguration['collection'])) {
            return 'collection';
        }
        if (isset($gridSourceConfiguration['query'])) {
            return 'query';
        }

        return '';
    }
}
