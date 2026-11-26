<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\GridSourceType;

use Hyva\Admin\Api\HyvaGridSourceProcessorInterface;
use Hyva\Admin\Model\DataType\ScalarAndNullDataType;
use Hyva\Admin\Model\DataType\TextDataType;
use Hyva\Admin\Model\GridSourceType\ArrayProviderGridSourceType;
use Hyva\Admin\Test\Integration\TestingGridDataProvider;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

use function array_map as map;

/**
 * @magentoAppArea adminhtml
 */
class ArrayProviderGridSourceTypeTest extends TestCase
{
    private function createArrayProviderGridSourceTypeWithArray(
        array $testGridData,
        array $processors = []
    ): ArrayProviderGridSourceType {
        $name                = 'test-grid';
        $sourceConfiguration = ['arrayProvider' => TestingGridDataProvider::withArray($testGridData)];

        $constructorArguments = [
            'gridName'            => $name,
            'sourceConfiguration' => $sourceConfiguration,
            'processors'          => $processors,
        ];
        return ObjectManager::getInstance()->create(ArrayProviderGridSourceType::class, $constructorArguments);
    }

    public function testReturnsTheColumnKeysOfTheFirstRow(): void
    {
        $sut = $this->createArrayProviderGridSourceTypeWithArray([
            ['aaa' => 111, 'bbb' => 222],
            ['aaa' => 111, 'bbb' => 222, 'ccc' => 333],
        ]);
        $this->assertSame(['aaa', 'bbb'], $sut->getColumnKeys());
    }

    public function testExtractsValueFromGivenArray(): void
    {
        $sut   = $this->createArrayProviderGridSourceTypeWithArray([]);
        $value = new \stdClass();
        $this->assertSame($value, $sut->extractValue(['foo' => $value], 'foo'));
        $this->assertSame($value, $sut->extractValue(['bar' => $value], 'bar'));
    }

    public function testReturnsNullWhenUnableToExtractValue(): void
    {
        $sut = $this->createArrayProviderGridSourceTypeWithArray([]);

        $this->assertNull($sut->extractValue([], 'bar'));
    }

    public function testExtractsBasicColumnDefinition(): void
    {
        $value = null;

        $key = 'x';
        $sut = $this->createArrayProviderGridSourceTypeWithArray([[$key => $value]]);

        $columnDefinition = $sut->getColumnDefinition($key);

        $this->assertSame($key, $columnDefinition->getKey());
        $this->assertSame(ScalarAndNullDataType::TYPE_SCALAR_NULL, $columnDefinition->getType());
    }

    public function testHandlesNumericColumnKeysGracefully(): void
    {
        $rowWithNumericKeys = ['aaa', 'bbb'];
        $sut                = $this->createArrayProviderGridSourceTypeWithArray([$rowWithNumericKeys]);
        $this->assertSame([0, 1], $sut->getColumnKeys());
        $this->assertSame(TextDataType::TYPE_TRUNCATED_TEXT, $sut->getColumnDefinition('0')->getType());
        $this->assertSame(TextDataType::TYPE_TRUNCATED_TEXT, $sut->getColumnDefinition('1')->getType());
    }

    public function testReturnsAndExtractsGridData(): void
    {
        $testGridData = [
            ['aaa' => 111, 'bbb' => 222],
            ['aaa' => 111, 'bbb' => 222, 'ccc' => 333],
        ];

        $sut              = $this->createArrayProviderGridSourceTypeWithArray($testGridData);
        $rawDataContainer = $sut->fetchData(new SearchCriteria());
        $actualData       = $sut->extractRecords($rawDataContainer);

        $this->assertSame($testGridData, $actualData);
    }

    public function testCountsWithFiltersApplied(): void
    {
        $testGridData   = [
            ['aaa' => 111, 'bbb' => 222, 'ccc' => 999],
            ['aaa' => 111, 'bbb' => 222, 'ccc' => 333],
        ];
        $filter         = new Filter(['field' => 'ccc', 'value' => 999, 'condition_type' => 'eq']);
        $filterGroup    = new FilterGroup(['filters' => [$filter]]);
        $searchCriteria = new SearchCriteria(['filter_groups' => [$filterGroup]]);

        $sut              = $this->createArrayProviderGridSourceTypeWithArray($testGridData);
        $rawDataContainer = $sut->fetchData($searchCriteria);
        $count            = $sut->extractTotalRowCount($rawDataContainer);
        $this->assertSame(1, $count);
    }

    public function testAppliesProcessors(): void
    {
        $testGridData = [
            ['aaa' => 111, 'bbb' => 222],
            ['aaa' => 111, 'bbb' => 222],
        ];

        $processor = new class() implements HyvaGridSourceProcessorInterface {
            public function beforeLoad($source, SearchCriteriaInterface $searchCriteria, string $gridName): void
            {
                $searchCriteria->setPageSize(1);
            }

            public function afterLoad($rawResult, SearchCriteriaInterface $searchCriteria, string $gridName)
            {
                $rawResult[0]['aaa'] = $rawResult[0]['aaa'] + 1;

                return $rawResult;
            }
        };

        $sut              = $this->createArrayProviderGridSourceTypeWithArray($testGridData, [$processor]);
        $rawDataContainer = $sut->fetchData(new SearchCriteria());
        $actualData       = $sut->extractRecords($rawDataContainer);

        $this->assertSame([['aaa' => 112, 'bbb' => 222]], $actualData);
    }

    private function createSearchCriteriaWithFilter(string $field, $value, string $conditionType): SearchCriteria
    {
        $filter      = new Filter(['field' => $field, 'value' => $value, 'condition_type' => $conditionType]);
        $filterGroup = new FilterGroup(['filters' => [$filter]]);
        return new SearchCriteria(['filter_groups' => [$filterGroup]]);
    }

    public function testAppliesFiltersAfterColumnReflection(): void
    {
        $testGridData = [
            ['id' => 1, 'name' => 'alice'],
            ['id' => 2, 'name' => 'bob'],
            ['id' => 3, 'name' => 'carol'],
        ];
        $sut          = $this->createArrayProviderGridSourceTypeWithArray($testGridData);

        // Column reflection fetches the first row before the records are fetched with the grid search criteria
        $sut->getColumnKeys();
        $sut->getColumnDefinition('name');

        $rawDataContainer = $sut->fetchData($this->createSearchCriteriaWithFilter('name', 'bob', 'eq'));

        $this->assertSame([['id' => 2, 'name' => 'bob']], $sut->extractRecords($rawDataContainer));
        $this->assertSame(1, $sut->extractTotalRowCount($rawDataContainer));
    }

    public function testAppliesTheFiltersOfEachSearchCriteria(): void
    {
        $testGridData = [
            ['id' => 1, 'name' => 'alice'],
            ['id' => 2, 'name' => 'bob'],
            ['id' => 3, 'name' => 'carol'],
        ];
        $sut          = $this->createArrayProviderGridSourceTypeWithArray($testGridData);

        $first  = $sut->fetchData($this->createSearchCriteriaWithFilter('id', 2, 'gteq'));
        $second = $sut->fetchData($this->createSearchCriteriaWithFilter('name', 'a%', 'like'));
        $third  = $sut->fetchData(new SearchCriteria());

        $this->assertSame([['id' => 2, 'name' => 'bob'], ['id' => 3, 'name' => 'carol']], $sut->extractRecords($first));
        $this->assertSame(2, $sut->extractTotalRowCount($first));

        $this->assertSame([['id' => 1, 'name' => 'alice']], $sut->extractRecords($second));
        $this->assertSame(1, $sut->extractTotalRowCount($second));

        $this->assertSame($testGridData, $sut->extractRecords($third));
        $this->assertSame(3, $sut->extractTotalRowCount($third));
    }

    public function testCountsFilteredRecordsNotPageSize(): void
    {
        $testGridData   = [
            ['id' => 1, 'type' => 'x'],
            ['id' => 2, 'type' => 'y'],
            ['id' => 3, 'type' => 'x'],
            ['id' => 4, 'type' => 'x'],
        ];
        $searchCriteria = $this->createSearchCriteriaWithFilter('type', 'x', 'eq');
        $searchCriteria->setPageSize(2);
        $searchCriteria->setCurrentPage(2);

        $sut              = $this->createArrayProviderGridSourceTypeWithArray($testGridData);
        $rawDataContainer = $sut->fetchData($searchCriteria);

        $this->assertSame([['id' => 4, 'type' => 'x']], $sut->extractRecords($rawDataContainer));
        $this->assertSame(3, $sut->extractTotalRowCount($rawDataContainer));
    }

    public function testReturnsAllFilteredRecordsWhenPagingThroughResults(): void
    {
        $testGridData = map(function (int $id): array {
            return ['id' => $id, 'type' => $id % 2 ? 'odd' : 'even'];
        }, range(1, 9));
        $sut          = $this->createArrayProviderGridSourceTypeWithArray($testGridData);
        $sut->getColumnKeys();

        // Same paging loop as the grid exports: same criteria, increasing page number until the total is reached
        $searchCriteria = $this->createSearchCriteriaWithFilter('type', 'odd', 'eq');
        $searchCriteria->setPageSize(2);
        $records = [];
        $page    = 1;
        do {
            $searchCriteria->setCurrentPage($page++);
            $rawDataContainer = $sut->fetchData($searchCriteria);
            $records          = array_merge($records, $sut->extractRecords($rawDataContainer));
        } while (count($records) < $sut->extractTotalRowCount($rawDataContainer) && $page < 10);

        $this->assertSame([1, 3, 5, 7, 9], array_column($records, 'id'));
        $this->assertSame(4, $page, 'Expected exactly three pages to be fetched');
    }

    public function testAppliesFiltersAndSortOrdersTogether(): void
    {
        $testGridData   = [
            ['id' => 1, 'type' => 'x'],
            ['id' => 2, 'type' => 'y'],
            ['id' => 3, 'type' => 'x'],
        ];
        $searchCriteria = $this->createSearchCriteriaWithFilter('type', 'x', 'eq');
        $searchCriteria->setSortOrders([new SortOrder(['field' => 'id', 'direction' => SortOrder::SORT_DESC])]);

        $sut = $this->createArrayProviderGridSourceTypeWithArray($testGridData);
        $sut->getColumnKeys();
        $rawDataContainer = $sut->fetchData($searchCriteria);

        $this->assertSame([['id' => 3, 'type' => 'x'], ['id' => 1, 'type' => 'x']], $sut->extractRecords($rawDataContainer));
        $this->assertSame(2, $sut->extractTotalRowCount($rawDataContainer));
    }

    public function testReturnsNoRecordsIfNothingMatchesTheFilters(): void
    {
        $sut = $this->createArrayProviderGridSourceTypeWithArray([['id' => 1], ['id' => 2]]);
        $sut->getColumnKeys();

        $rawDataContainer = $sut->fetchData($this->createSearchCriteriaWithFilter('id', 3, 'eq'));

        $this->assertSame([], $sut->extractRecords($rawDataContainer));
        $this->assertSame(0, $sut->extractTotalRowCount($rawDataContainer));
        $this->assertSame(['id'], $sut->getColumnKeys(), 'Column keys must not depend on the filtered result');
    }

    public function testLoadsProviderDataOnlyOnce(): void
    {
        $sut = $this->createArrayProviderGridSourceTypeWithArray([['id' => 1], ['id' => 2]]);

        $sut->getColumnKeys();
        $sut->getColumnDefinition('id');
        $sut->fetchData($this->createSearchCriteriaWithFilter('id', 1, 'eq'));
        $sut->fetchData($this->createSearchCriteriaWithFilter('id', 2, 'eq'));

        $this->assertSame(1, TestingGridDataProvider::getLoadCount());
    }

    public function testPassesTheSearchCriteriaOfEachFetchToTheProcessors(): void
    {
        $testGridData = [['id' => 1], ['id' => 2]];

        $processor = new class() implements HyvaGridSourceProcessorInterface {
            public $beforeLoadCriteria = [];
            public $afterLoadCriteria = [];
            public $afterLoadRecords = [];

            public function beforeLoad($source, SearchCriteriaInterface $searchCriteria, string $gridName): void
            {
                $this->beforeLoadCriteria[] = $searchCriteria;
            }

            public function afterLoad($rawResult, SearchCriteriaInterface $searchCriteria, string $gridName)
            {
                $this->afterLoadCriteria[] = $searchCriteria;
                $this->afterLoadRecords[]  = $rawResult;
                return null;
            }
        };

        $sut = $this->createArrayProviderGridSourceTypeWithArray($testGridData, [$processor]);
        $sut->getColumnKeys();
        $processor->beforeLoadCriteria = $processor->afterLoadCriteria = $processor->afterLoadRecords = [];

        $searchCriteria = $this->createSearchCriteriaWithFilter('id', 2, 'eq');
        $sut->fetchData($searchCriteria);

        $this->assertSame([$searchCriteria], $processor->beforeLoadCriteria);
        $this->assertSame([$searchCriteria], $processor->afterLoadCriteria);
        $this->assertSame([[['id' => 2]]], $processor->afterLoadRecords, 'afterLoad should receive filtered records');
    }

    public function testReflectsColumnsAddedByProcessorsOnFirstRowOnlyOnce(): void
    {
        $processor = new class() implements HyvaGridSourceProcessorInterface {
            public $afterLoadCount = 0;

            public function beforeLoad($source, SearchCriteriaInterface $searchCriteria, string $gridName): void
            {
            }

            public function afterLoad($rawResult, SearchCriteriaInterface $searchCriteria, string $gridName)
            {
                $this->afterLoadCount++;
                return array_map(function (array $row): array {
                    return $row + ['added' => 'by processor'];
                }, $rawResult);
            }
        };

        $sut = $this->createArrayProviderGridSourceTypeWithArray([['id' => 1], ['id' => 2]], [$processor]);

        $this->assertSame(['id', 'added'], $sut->getColumnKeys());
        $sut->getColumnDefinition('id');
        $sut->getColumnDefinition('added');
        $this->assertSame(1, $processor->afterLoadCount);
    }
}
