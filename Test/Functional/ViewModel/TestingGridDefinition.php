<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Functional\ViewModel;

use Hyva\Admin\Model\HyvaGridDefinitionInterface;
use Hyva\Admin\Model\HyvaGridDefinitionInterfaceFactory;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;

class TestingGridDefinition implements HyvaGridDefinitionInterface
{
    private array $gridDefinition;

    public static function makeFactory(array $testingGridDefinition): HyvaGridDefinitionInterfaceFactory
    {
        return new class($testingGridDefinition) extends HyvaGridDefinitionInterfaceFactory
        {
            private array $gridDefinition;

            /** @noinspection PhpMissingParentConstructorInspection */
            public function __construct(array $gridDefinition)
            {
                $this->gridDefinition = $gridDefinition;
            }

            public function create(array $data = [])
            {
                return new TestingGridDefinition($this->gridDefinition);
            }

        };
    }

    public function __construct(array $gridDefinition)
    {
        $this->gridDefinition = $gridDefinition;
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
}
