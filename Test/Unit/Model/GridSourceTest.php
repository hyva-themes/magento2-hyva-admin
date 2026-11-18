<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model;

use function array_combine as zip;
use function array_map as map;

use Hyva\Admin\Model\GridSource;
use Hyva\Admin\Model\GridSourcePrefetchEventDispatcher;
use Hyva\Admin\Model\GridSourceType\GridSourceTypeInterface;
use Hyva\Admin\Model\MethodValueBindings;
use Hyva\Admin\Model\RawGridSourceContainer;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinition;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\ObjectFactory;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Event\ManagerInterface;
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
            $this->createStub(GridSourcePrefetchEventDispatcher::class),
            $this->createStub(GridSource\SearchCriteriaBindings::class),
            $this->createStub(GridSource\SearchCriteriaIdentity::class)
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
        $stubObjectManager = $this->createStub(ObjectManagerInterface::class);
        $stubObjectManager->method('create')->willReturnCallback([$this, 'createColumnDefinitionCallback']);

        return $stubObjectManager;
    }

    private function createStubGridSourceType(array $sourceColumnKeys)
    {
        $gridSourceType = $this->createStub(GridSourceTypeInterface::class);
        $gridSourceType->method('getColumnKeys')->willReturn($sourceColumnKeys);
        $gridSourceType->method('getColumnDefinition')->willReturnCallback([$this, 'createColumnDefinitionByKey']);

        return $gridSourceType;
    }

    public static function repeatedReadCriteriaProvider(): array
    {
        $bindings = [
            ['field' => 'store_id', 'value' => 1],
            ['field' => 'store_id', 'value' => 2],
        ];
        return [
            'AND defaults' => ['and', $bindings, false],
            'OR defaults' => ['or', $bindings, false],
            'no defaults' => ['and', [], false],
            'prefetch filter' => ['and', $bindings, true],
        ];
    }

    /**
     * @dataProvider repeatedReadCriteriaProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('repeatedReadCriteriaProvider')]
    public function testRepeatedReadsReuseDataWithoutChangingCallerCriteria(
        string $combineConditionsWith,
        array $bindingsConfig,
        bool $addPrefetchFilter
    ): void {
        $objectFactory = $this->createStub(ObjectFactory::class);
        $objectFactory->method('create')->willReturnCallback(function (string $type, array $arguments) {
            return new $type($arguments['data']);
        });
        $filterBuilder = new FilterBuilder($objectFactory);
        $bindings = new GridSource\SearchCriteriaBindings(
            new MethodValueBindings($this->createStub(ObjectManagerInterface::class)),
            $filterBuilder,
            new FilterGroupBuilder($objectFactory, $filterBuilder),
            $combineConditionsWith,
            $bindingsConfig
        );
        $filter = new Filter(['field' => 'status', 'value' => 'pending', 'condition_type' => 'eq']);
        $originalGroups = [new FilterGroup(['filters' => [$filter]])];
        $criteria = new SearchCriteria(['filter_groups' => $originalGroups]);
        $expectedCriteria = $bindings->apply(clone $criteria);
        $prefetchGroup = new FilterGroup(['filters' => [
            new Filter(['field' => 'is_active', 'value' => 1, 'condition_type' => 'eq']),
        ]]);
        if ($addPrefetchFilter) {
            $expectedCriteria->setFilterGroups(array_merge($expectedCriteria->getFilterGroups(), [$prefetchGroup]));
        }
        $eventManager = $this->createStub(ManagerInterface::class);
        $eventManager->method('dispatch')->willReturnCallback(function (string $event, array $data) use (
            $addPrefetchFilter,
            $prefetchGroup
        ) {
            if ($addPrefetchFilter && $event === 'hyva_grid_source_prefetch_test') {
                $criteria = $data['search_criteria_container']->getSearchCriteria();
                $criteria->setFilterGroups(array_merge($criteria->getFilterGroups(), [$prefetchGroup]));
            }
        });
        $fetchCount = 0;
        $sourceType = $this->createStub(GridSourceTypeInterface::class);
        $sourceType->method('getRecordType')->willReturn('test_record');
        $sourceType->method('fetchData')->willReturnCallback(function (SearchCriteriaInterface $effectiveCriteria) use (
            &$fetchCount,
            $expectedCriteria
        ): RawGridSourceContainer {
            $this->assertEquals($expectedCriteria, $effectiveCriteria);
            $fetchCount++;
            return RawGridSourceContainer::forData([]);
        });
        $sourceType->method('extractRecords')->willReturn([['entity_id' => 1]]);
        $sourceType->method('extractTotalRowCount')->willReturn(1);
        $sut = new GridSource(
            'test',
            $sourceType,
            new GridSourcePrefetchEventDispatcher($eventManager),
            $bindings,
            new GridSource\SearchCriteriaIdentity()
        );

        for ($i = 0; $i < 3; $i++) {
            $this->assertSame([['entity_id' => 1]], $sut->getRecords($criteria));
            $this->assertSame(1, $sut->getTotalCount($criteria));
        }
        $this->assertSame(1, $fetchCount);
        $this->assertSame($originalGroups, $criteria->getFilterGroups());
        $this->assertSame('pending', $filter->getValue());

        $filter->setValue('complete');
        $sut->getRecords($criteria);
        $sut->getTotalCount($criteria);
        $this->assertSame(2, $fetchCount);
        $this->assertSame($originalGroups, $criteria->getFilterGroups());
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
