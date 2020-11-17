<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Model\Config\GridConfigReader;

class HyvaGridDefinition implements HyvaGridDefinitionInterface
{
    private string $gridName;

    private GridConfigReader $gridConfigReader;

    private ?array $memoizedGridConfig = null;

    public function __construct(string $gridName, GridConfigReader $gridConfigReader)
    {
        $this->gridName         = $gridName;
        $this->gridConfigReader = $gridConfigReader;
    }

    public function getName(): string
    {
        return $this->gridName;
    }

    private function getGridConfiguration(): array
    {
        if (! isset($this->memoizedGridConfig)) {
            $this->memoizedGridConfig = $this->gridConfigReader->getGridConfiguration($this->gridName);
        }
        return $this->memoizedGridConfig;
    }

    public function getIncludedColumns(): array
    {

    }

    public function getExcludedColumnKeys(): array
    {

    }

    public function getSourceConfig(): array
    {

    }
}
