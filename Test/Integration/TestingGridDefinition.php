<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration;

use Hyva\Admin\Model\HyvaGridDefinitionInterface;
use Hyva\Admin\Model\HyvaGridDefinitionInterfaceFactory;

class TestingGridDefinition implements HyvaGridDefinitionInterface
{
    private $gridDefinition;

    private $gridName;

    public static function makeFactory(string $name, array $testingGridDefinition): HyvaGridDefinitionInterfaceFactory
    {
        return new class($name, $testingGridDefinition) extends HyvaGridDefinitionInterfaceFactory
        {
            private $gridDefinition;

            private $gridName;

            public function __construct(string $gridName, array $gridDefinition)
            {
                $this->gridDefinition = $gridDefinition;
                $this->gridName = $gridName;
            }

            public function create(array $data = [])
            {
                return new TestingGridDefinition($this->gridName, $this->gridDefinition);
            }

        };
    }

    public function __construct(string $gridName, array $gridDefinition)
    {
        $this->gridDefinition = $gridDefinition;
        $this->gridName = $gridName;
    }

    public function getName(): string
    {
        return $this->gridName;
    }

    public function getColumnDefinitions(): array
    {
        return $this->gridDefinition['columns']['exclude'] ?? [];
    }

    public function getIncludedColumns(): array
    {
        return $this->gridDefinition['columns']['include'] ?? [];
    }

    public function getExcludedColumnKeys(): array
    {
        return $this->gridDefinition['columns']['exclude'] ?? [];
    }

    public function getSourceConfig(): array
    {
        return $this->gridDefinition['source'] ?? [];
    }

    public function getEntityDefinitionConfig(): array
    {
        return $this->gridDefinition['entityConfig'] ?? [];
    }

    public function getActionsConfig(): array
    {
        return $this->gridDefinition['actions'] ?? [];
    }

    public function getRowAction(): ?string
    {
        return $this->gridDefinition['columns']['@rowAction'] ?? null;
    }

    public function getMassActionConfig(): array
    {
        return $this->gridDefinition['massActions'] ?? [];
    }

    public function isKeepSourceColumns(): bool
    {
        return 'true' === ($this->gridDefinition['columns']['@keepColumnsFromSource'] ?? 'false');
    }

    public function getNavigationConfig(): array
    {
        return $this->gridDefinition['navigation'] ?? [];
    }
}
