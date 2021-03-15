<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Model\Config\GridConfigReader;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;

use function array_combine as zip;
use function array_map as map;

class HyvaGridDefinition implements HyvaGridDefinitionInterface
{
    /**
     * @var string
     */
    private $gridName;

    /**
     * @var GridConfigReader
     */
    private $gridConfigReader;

    /**
     * @var array|null
     */
    private $memoizedGridConfig = null;

    /**
     * @var ColumnDefinitionInterfaceFactory
     */
    private $columnDefinitionFactory;

    public function __construct(
        string $gridName,
        GridConfigReader $gridConfigReader,
        ColumnDefinitionInterfaceFactory $columnDefinitionFactory
    ) {
        $this->gridName = $gridName;
        $this->gridConfigReader = $gridConfigReader;
        $this->columnDefinitionFactory = $columnDefinitionFactory;
    }

    public function getName(): string
    {
        return $this->gridName;
    }

    private function getGridConfiguration(): array
    {
        if (!isset($this->memoizedGridConfig)) {
            $this->memoizedGridConfig = $this->gridConfigReader->getGridConfiguration($this->gridName);
        }
        return $this->memoizedGridConfig;
    }

    public function getIncludedColumns(): array
    {
        $columns = map(function (array $columnConfig): ColumnDefinitionInterface {
            return $this->columnDefinitionFactory->create($columnConfig);
        }, $this->getGridConfiguration()['columns']['include'] ?? []);
        return zip($this->extractKeys(...$columns), $columns);
    }

    /**
     * @param ColumnDefinitionInterface ...$columnDefinitions
     * @return string[]
     */
    private function extractKeys(ColumnDefinitionInterface ...$columnDefinitions): array
    {
        return map(function (ColumnDefinitionInterface $columnDefinition): string {
            return $columnDefinition->getKey();
        }, $columnDefinitions);
    }

    public function getExcludedColumnKeys(): array
    {
        return $this->getGridConfiguration()['columns']['exclude'] ?? [];
    }

    public function getSourceConfig(): array
    {
        return $this->getGridConfiguration()['source'] ?? [];
    }

    public function getEntityDefinitionConfig(): array
    {
        return $this->getGridConfiguration()['entity'] ?? [];
    }

    public function getActionsConfig(): array
    {
        return $this->getGridConfiguration()['actions'] ?? [];
    }

    public function getRowAction(): ?string
    {
        return $this->getGridConfiguration()['columns']['@rowAction'] ?? null;
    }

    public function getMassActionConfig(): array
    {
        return $this->getGridConfiguration()['massActions'] ?? [];
    }

    public function isKeepSourceColumns(): bool
    {
        return 'true' === ($this->getGridConfiguration()['columns']['@keepAllSourceCols'] ?? 'false');
    }

    public function getNavigationConfig(): array
    {
        return $this->getGridConfiguration()['navigation'] ?? [];
    }
}
