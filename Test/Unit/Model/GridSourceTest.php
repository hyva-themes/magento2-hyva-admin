<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model;

use function array_combine as zip;
use function array_map as map;

use Hyva\Admin\Model\GridSource;
use Hyva\Admin\Model\GridSourcePrefetchEventDispatcher;
use Hyva\Admin\Model\GridSourceType\GridSourceTypeInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinition;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;

class GridSourceTest extends TestCase
{
    private function assertContainsColumn(ColumnDefinitionInterface $actual, array $columns, string $msg = ''): void
    {
        $this->assertThat($actual, new ConstraintContainsColumn($columns), $msg);
    }

    public function createColumnDefinitionByKey(string $key): ColumnDefinitionInterface
    {
        return new ColumnDefinition($this->createStubColumnDefinitionObjectManager(), $key);
    }

    public function createColumnDefinitionFromArray(array $constructorArguments): ColumnDefinitionInterface
    {
        return new ColumnDefinition(
            $this->createStubColumnDefinitionObjectManager(),
            $constructorArguments['key'] ?? null,
            $constructorArguments['label'] ?? null,
            $constructorArguments['type'] ?? null,
            $constructorArguments['sortOrder'] ?? null,
            $constructorArguments['renderAsUnsecureHtml'] ?? null,
            $constructorArguments['template'] ?? null,
            $constructorArguments['rendererBlockName'] ?? null,
            $constructorArguments['sortable'] ?? null,
            $constructorArguments['source'] ?? null,
            $constructorArguments['options'] ?? null,
            $constructorArguments['isVisible'] ?? null,
            $constructorArguments['initiallyHidden'] ?? null,
        );
    }

    private function createGridSource(string $gridName, GridSourceTypeInterface $gridSourceType): GridSource
    {
        return new GridSource(
            $gridName,
            $gridSourceType,
            $this->createMock(GridSourcePrefetchEventDispatcher::class),
            $this->createMock(GridSource\SearchCriteriaBindings::class),
            $this->createMock(GridSource\SearchCriteriaIdentity::class)
        );
    }

    public function createColumnDefinitionCallback(string $type, array $constructorArguments): ColumnDefinitionInterface
    {
        if ($type !== ColumnDefinitionInterface::class) {
            throw new \OutOfBoundsException('Only ColumnDefinitions should be instantiated here');
        }
        return $this->createColumnDefinitionFromArray($constructorArguments);
    }

    private function createStubColumnDefinitionObjectManager(): ObjectManagerInterface
    {
        $stubObjectManager = $this->createMock(ObjectManagerInterface::class);
        $stubObjectManager->method('create')->willReturnCallback([$this, 'createColumnDefinitionCallback']);

        return $stubObjectManager;
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
        $gridSourceType = $this->createStubGridSourceType(['foo', 'bar', 'baz']);

        $configuredIncludeColumns = [
            'foo' => $this->createColumnDefinitionFromArray(['key' => 'foo']),
            'bar' => $this->createColumnDefinitionFromArray(['key' => 'bar']),
        ];

        $sut              = $this->createGridSource('test', $gridSourceType);
        $extractedColumns = $sut->extractColumnDefinitions($configuredIncludeColumns, [], false);

        $expectedColumnDefinitionFoo = $this->createColumnDefinitionFromArray(
            ['key' => 'foo', 'isVisible' => true, 'sortOrder' => '1']
        );
        $expectedColumnDefinitionBar = $this->createColumnDefinitionFromArray(
            ['key' => 'bar', 'isVisible' => true, 'sortOrder' => '2']
        );
        $expectedColumnDefinitionBaz = $this->createColumnDefinitionFromArray(
            ['key' => 'baz', 'isVisible' => false, 'sortOrder' => '5']
        );
        $this->assertContainsColumn($expectedColumnDefinitionFoo, $extractedColumns);
        $this->assertContainsColumn($expectedColumnDefinitionBar, $extractedColumns);
        $this->assertContainsColumn($expectedColumnDefinitionBaz, $extractedColumns);
    }

    public function testThrowsExceptionForUnavailableColumnKeys(): void
    {
        $gridSourceType = $this->createStubGridSourceType(['foo', 'bar', 'baz']);

        $configuredIncludeColumns = [
            $this->createColumnDefinitionFromArray(['key' => 'foo']),
            $this->createColumnDefinitionFromArray(['key' => 'bar']),
            $this->createColumnDefinitionFromArray(['key' => 'does_not_exist']),
        ];

        $sut = $this->createGridSource('test', $gridSourceType);
        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('Column(s) not found on source: ');

        $sut->extractColumnDefinitions($configuredIncludeColumns, [], false);
    }

    public function testMergesInIncludedColumnSpecifications(): void
    {
        $gridSourceType = $this->createStubGridSourceType(['foo', 'bar']);

        $configuredIncludeColumns = [
            'foo' => $this->createColumnDefinitionFromArray(['key' => 'foo', 'label' => 'Foo Label']),
            'bar' => $this->createColumnDefinitionFromArray(['key' => 'bar', 'type' => 'int']),
        ];

        $sut                         = $this->createGridSource('test', $gridSourceType);
        $extractedColumns            = $sut->extractColumnDefinitions($configuredIncludeColumns, [], false);
        $expectedColumnDefinitionFoo = $this->createColumnDefinitionFromArray(
            ['key' => 'foo', 'isVisible' => true, 'sortOrder' => '1', 'label' => 'Foo Label']
        );
        $expectedColumnDefinitionBar = $this->createColumnDefinitionFromArray(
            ['key' => 'bar', 'isVisible' => true, 'sortOrder' => '2', 'type' => 'int']
        );
        $this->assertContainsColumn($expectedColumnDefinitionFoo, $extractedColumns);
        $this->assertContainsColumn($expectedColumnDefinitionBar, $extractedColumns);
    }

    public function testExtractsAllColumnKeysFromSourceIfNoneAreConfigured(): void
    {
        $sourceColumnKeys = ['foo', 'bar', 'baz'];
        $gridSourceType   = $this->createStubGridSourceType($sourceColumnKeys);

        $configuredIncludeColumns = [];

        $sut              = $this->createGridSource('test', $gridSourceType);
        $extractedColumns = $sut->extractColumnDefinitions($configuredIncludeColumns, [], false);
        $extractedKeys    = map(function (ColumnDefinitionInterface $columnDefinition): string {
            return $columnDefinition->getKey();
        }, $extractedColumns);
        $this->assertSame(zip($sourceColumnKeys, $sourceColumnKeys), $extractedKeys);
    }
}
