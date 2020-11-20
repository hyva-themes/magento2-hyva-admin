<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Model\Config\GridConfigReader;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;

use function array_map as map;

class HyvaGridDefinition implements HyvaGridDefinitionInterface
{
    private string $gridName;

    private GridConfigReader $gridConfigReader;

    private ?array $memoizedGridConfig = null;

    private ColumnDefinitionInterfaceFactory $columnDefinitionFactory;

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
        return map(function (array $columnConfig): ColumnDefinitionInterface {
            return $this->columnDefinitionFactory->create($columnConfig);
        }, $this->getGridConfiguration()['columns']['include'] ?? []);
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

    public function keepColumnsFromSource(): bool
    {
        return 'true' === ($this->getGridConfiguration()['columns']['@keepAllSourceCols'] ?? 'false');
    }
}
