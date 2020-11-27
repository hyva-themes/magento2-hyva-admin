<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model;

use Hyva\Admin\Model\GridSource;
use Hyva\Admin\Model\GridSourceType\GridSourceTypeInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinition;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;

use function array_combine as zip;
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
        $dummyObjectManager = $this->createMock(ObjectManagerInterface::class);
        return new ColumnDefinition($dummyObjectManager, $key);
    }

    public function createColumnDefinitionFromArray(array $params): ColumnDefinitionInterface
    {
        return new ColumnDefinition(
            $this->createMock(ObjectManagerInterface::class),
            $params['key'],
            $params['label'] ?? null,
            $params['type'] ?? null
        );
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

    public function testAllColumnsEvenIfNotInIncludedColumns(): void
    {
        $dummyObjectManager          = $this->createMock(ObjectManagerInterface::class);
        $stubColumnDefinitionFactory = $this->createStubColumnDefinitionFactory();
        $gridSourceType              = $this->createStubGridSourceType(['foo', 'bar', 'baz']);
        $configuredIncludeColumns    = [
            'foo' => new ColumnDefinition($dummyObjectManager, 'foo'),
            'bar' => new ColumnDefinition($dummyObjectManager, 'bar'),
        ];

        $sut              = new GridSource($gridSourceType, $stubColumnDefinitionFactory);
        $extractedColumns = $sut->extractColumnDefinitions($configuredIncludeColumns);

        $this->assertContainsColumn(new ColumnDefinition($dummyObjectManager, 'foo'), $extractedColumns);
        $this->assertContainsColumn(new ColumnDefinition($dummyObjectManager, 'bar'), $extractedColumns);
        $this->assertContainsColumn(new ColumnDefinition($dummyObjectManager, 'baz'), $extractedColumns);
    }

    public function testThrowsExceptionForUnavailableColumnKeys(): void
    {
        $dummyObjectManager          = $this->createMock(ObjectManagerInterface::class);
        $stubColumnDefinitionFactory = $this->createStubColumnDefinitionFactory();
        $gridSourceType              = $this->createStubGridSourceType(['foo', 'bar', 'baz']);

        $configuredIncludeColumns = [
            new ColumnDefinition($dummyObjectManager, 'foo'),
            new ColumnDefinition($dummyObjectManager, 'bar'),
            new ColumnDefinition($dummyObjectManager, 'does_not_exist'),
        ];

        $sut = new GridSource($gridSourceType, $stubColumnDefinitionFactory);
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Column(s) not found on source: ');

        $sut->extractColumnDefinitions($configuredIncludeColumns);
    }

    public function testMergesInIncludedColumnSpecifications(): void
    {
        $dummyObjectManager          = $this->createMock(ObjectManagerInterface::class);
        $stubColumnDefinitionFactory = $this->createStubColumnDefinitionFactory();
        $gridSourceType              = $this->createStubGridSourceType(['foo', 'bar']);

        $configuredIncludeColumns = [
            'foo' => new ColumnDefinition($dummyObjectManager, 'foo', 'Foo Label'), // configured label
            'bar' => new ColumnDefinition($dummyObjectManager, 'bar', null, 'int'), // configured type
        ];

        $sut                       = new GridSource($gridSourceType, $stubColumnDefinitionFactory);
        $extractedColumns          = $sut->extractColumnDefinitions($configuredIncludeColumns);
        $expectedColumnDefinition1 = new ColumnDefinition($dummyObjectManager, 'foo', 'Foo Label');
        $expectedColumnDefinition2 = new ColumnDefinition($dummyObjectManager, 'bar', null, 'int');
        $this->assertContainsColumn($expectedColumnDefinition1, $extractedColumns);
        $this->assertContainsColumn($expectedColumnDefinition2, $extractedColumns);
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
        $this->assertSame(zip($sourceColumnKeys, $sourceColumnKeys), $extractedKeys);
    }

}
