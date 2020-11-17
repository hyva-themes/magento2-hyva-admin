<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model;

use Hyva\Admin\Model\GridSource;
use Hyva\Admin\Model\GridSourceType\GridSourceTypeInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinition;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;
use PHPUnit\Framework\TestCase;

use function array_map as map;

class GridSourceTest extends TestCase
{
    private function assertContainsColumn(ColumnDefinitionInterface $actual, array $columns, string $msg = ''): void
    {
        $this->assertThat($actual, new ConstraintContainsColumn($columns), $msg);
    }

    private function assertNotContainsColumnWithKey(string $actual, array $columns, string $msg = ''): void
    {
        $this->assertThat($actual, new ConstraintNotContainsColumnWithKey($columns), $msg);
    }

    public function createColumnDefinitionByKey(string $key): ColumnDefinitionInterface
    {
        return new ColumnDefinition($key);
    }

    public function createColumnDefinitionFromArray(array $params): ColumnDefinitionInterface
    {
        return new ColumnDefinition($params['key'], $params['label'] ?? null, $params['type'] ?? null);
    }

    private function createStubColumnDefinitionFactory()
    {
        $stubGridColumnDefinitionFactory = $this->createMock(ColumnDefinitionInterfaceFactory::class);
        $stubGridColumnDefinitionFactory->method('create')
                                        ->willReturnCallback([$this, 'createColumnDefinitionFromArray']);

        return $stubGridColumnDefinitionFactory;
    }

    private function createStubGridSourceType(array $sourceColumnKeys)
    {
        $gridSourceType = $this->createMock(GridSourceTypeInterface::class);
        $gridSourceType->method('getColumnKeys')->willReturn($sourceColumnKeys);
        $gridSourceType->method('getColumnDefinition')->willReturnCallback([$this, 'createColumnDefinitionByKey']);

        return $gridSourceType;
    }

    public function testExtractsOnlyIncludedColumnsIfSpecified(): void
    {
        $stubColumnDefinitionFactory = $this->createStubColumnDefinitionFactory();
        $gridSourceType              = $this->createStubGridSourceType(['foo', 'bar', 'baz']);
        $configuredIncludeColumns    = [
            new ColumnDefinition('foo'),
            new ColumnDefinition('bar'),
        ];

        $sut              = new GridSource($gridSourceType, $stubColumnDefinitionFactory);
        $extractedColumns = $sut->extractColumnDefinitions($configuredIncludeColumns);

        $this->assertContainsColumn(new ColumnDefinition('foo'), $extractedColumns);
        $this->assertContainsColumn(new ColumnDefinition('bar'), $extractedColumns);
        $this->assertNotContainsColumnWithKey('baz', $extractedColumns);
    }

    public function testThrowsExceptionForUnavailableColumnKeys(): void
    {
        $stubColumnDefinitionFactory = $this->createStubColumnDefinitionFactory();
        $gridSourceType              = $this->createStubGridSourceType(['foo', 'bar', 'baz']);

        $configuredIncludeColumns = [
            new ColumnDefinition('foo'),
            new ColumnDefinition('bar'),
            new ColumnDefinition('does_not_exist'),
        ];

        $sut = new GridSource($gridSourceType, $stubColumnDefinitionFactory);
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Column(s) not found on source: ');

        $sut->extractColumnDefinitions($configuredIncludeColumns);
    }

    public function testMergesInIncludedColumnSpecifications(): void
    {
        $stubColumnDefinitionFactory = $this->createStubColumnDefinitionFactory();
        $gridSourceType              = $this->createStubGridSourceType(['foo', 'bar']);

        $configuredIncludeColumns = [
            new ColumnDefinition('foo', 'Foo Label'), // configured label
            new ColumnDefinition('bar', null, 'int'), // configured type
        ];

        $sut              = new GridSource($gridSourceType, $stubColumnDefinitionFactory);
        $extractedColumns = $sut->extractColumnDefinitions($configuredIncludeColumns);
        $this->assertContainsColumn(new ColumnDefinition('foo', 'Foo Label', 'string'), $extractedColumns);
        $this->assertContainsColumn(new ColumnDefinition('bar', 'Bar', 'int'), $extractedColumns);
    }

    public function testExtractsAllColumnKeysFromSourceIfNoneAreConfigured(): void
    {
        $sourceColumnKeys            = ['foo', 'bar', 'baz'];
        $stubColumnDefinitionFactory = $this->createStubColumnDefinitionFactory();
        $gridSourceType              = $this->createStubGridSourceType($sourceColumnKeys);

        $configuredIncludeColumns = [];

        $sut              = new GridSource($gridSourceType, $stubColumnDefinitionFactory);
        $extractedColumns = $sut->extractColumnDefinitions($configuredIncludeColumns);
        $extractedKeys    = map(function (ColumnDefinitionInterface $columnDefinition): string {
            return $columnDefinition->getKey();
        }, $extractedColumns);
        $this->assertSame($sourceColumnKeys, $extractedKeys);
    }

}
