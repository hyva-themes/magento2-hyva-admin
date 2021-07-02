<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\GridSourceType;

use Hyva\Admin\Api\HyvaGridSourceProcessorInterface;
use Hyva\Admin\Model\GridSourceType\QueryGridSourceType;
use Hyva\Admin\Model\GridSourceType\RawGridSourceDataAccessor;
use Hyva\Admin\Model\RawGridSourceContainer;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface as DbAdapter;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Select;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

use function array_filter as filter;
use function array_keys as keys;
use function array_map as map;
use function array_values as values;

/**
 * @magentoDbIsolation disabled
 */
class QueryGridSourceTypeTest extends TestCase
{
    private $fixtureTables = [];

    /**
     * @after
     */
    public function removeFixtureTables(): void
    {
        $this->dropTableIfExists(...$this->fixtureTables);
    }

    private function dropTableIfExists(string ...$tables): void
    {
        $db = $this->getConnection();
        if (0 === $db->getTransactionLevel()) {
            map([$db, 'dropTable'], filter($tables, [$db, 'isTableExists']));
        }
    }

    private function createFixtureTable(string $tableName, array $tableData): void
    {
        $this->dropTableIfExists($tableName);
        $this->fixtureTables[] = $tableName;

        $firstRow    = values($tableData)[0];
        $columnNames = keys($firstRow);
        $columnTypes = map(function ($v): string {
            $types = ['double' => Table::TYPE_FLOAT, 'integer' => Table::TYPE_INTEGER];
            return $types[gettype($v)] ?? Table::TYPE_TEXT;
        }, $firstRow);

        $table = new Table();
        $table->setName($tableName);

        map(function (string $columnName, string $type) use ($table): void {
            $table->addColumn($columnName, $type);
        }, $columnNames, $columnTypes);

        $this->getConnection()->createTable($table);
        $this->getConnection()->insertMultiple($tableName, $tableData);

    }

    private function getConnection(): DbAdapter
    {
        return ObjectManager::getInstance()->get(ResourceConnection::class)->getConnection();
    }

    private function createQueryGridSourceType(array $queryConfig, array $processors = []): QueryGridSourceType
    {
        $args = [
            'gridName'            => 'test',
            'processors'          => $processors,
            'sourceConfiguration' => [
                'query' => $queryConfig,
            ],
        ];
        return ObjectManager::getInstance()->create(QueryGridSourceType::class, $args);
    }

    private function unboxGridData(RawGridSourceContainer $result)
    {
        $accessor = ObjectManager::getInstance()->get(RawGridSourceDataAccessor::class);
        return $accessor->unbox($result)['data'];
    }

    public function testReturnsColumnsFromSelect(): void
    {
        $queryConfig = [
            'select' => [
                'from'    => ['table' => 'foo'],
                'columns' => [['column' => 'a'], ['column' => 'b']],
            ],
        ];

        $sut = $this->createQueryGridSourceType($queryConfig);

        $this->assertSame(['a', 'b'], $sut->getColumnKeys());
    }

    public function testReturnsAllColumnsByDefault(): void
    {
        $tableData = [
            ['a' => '1', 'b' => '2'],
            ['a' => '3', 'b' => '4'],
        ];
        $this->createFixtureTable('foo', $tableData);

        $queryConfig = [
            'select' => [
                'from' => ['table' => 'foo'],
            ],
        ];

        $sut = $this->createQueryGridSourceType($queryConfig);

        $result = $sut->fetchData(new SearchCriteria());
        $this->assertSame(2, $sut->extractTotalRowCount($result));
        $this->assertSame($tableData, $this->unboxGridData($result));
    }

    public function testReturnsOnlySpecifiedColumns(): void
    {
        $tableData = [
            ['a' => '1', 'b' => '2'],
            ['a' => '3', 'b' => '4'],
            ['a' => '5', 'b' => '6'],
        ];
        $this->createFixtureTable('foo', $tableData);

        $queryConfig = [
            'select' => [
                'from'    => ['table' => 'foo'],
                'columns' => [['column' => 'b']],
            ],
        ];

        $sut = $this->createQueryGridSourceType($queryConfig);

        $result = $sut->fetchData(new SearchCriteria());
        $this->assertSame(3, $sut->extractTotalRowCount($result));
        $this->assertSame([['b' => '2'], ['b' => '4'], ['b' => '6']], $this->unboxGridData($result));
    }

    public function testJoinsTables(): void
    {
        $tableData1 = [
            ['val1' => 'aaa1', 'b_id' => '2'],
            ['val1' => 'aaa2', 'b_id' => '4'],
        ];
        $tableData2 = [
            ['val2' => 'rec1', 'id' => '2', 'n' => '5'],
            ['val2' => 'rec2', 'id' => '4', 'n' => '6'],
        ];
        $tableData3 = [
            ['val3' => 'xx1', 'c_id' => '5'],
            ['val3' => 'xx2', 'c_id' => '6'],
        ];
        $this->createFixtureTable('foo1', $tableData1);
        $this->createFixtureTable('foo2', $tableData2);
        $this->createFixtureTable('foo3', $tableData3);

        $queryConfig = [
            'select' => [
                'from'    => ['table' => 'foo1'],
                'columns' => [['column' => 'val1', '@as' => 'value']],
                'joins'   => [
                    [
                        'type' => 'left',
                        'join' => ['table' => 'foo2'],
                        'on'   => 'foo1.b_id=foo2.id',
                    ],
                    [
                        'type'    => 'left',
                        'join'    => ['table' => 'foo3'],
                        'on'      => 'foo2.n=foo3.c_id',
                        'columns' => [
                            ['column' => 'val3', '@as' => 'name'],
                        ],
                    ],
                ],
            ],
        ];

        $sut = $this->createQueryGridSourceType($queryConfig);

        $result = $sut->fetchData(new SearchCriteria());
        $this->assertSame(2, $sut->extractTotalRowCount($result));

        $expected = [
            ['value' => 'aaa1', 'name' => 'xx1'],
            ['value' => 'aaa2', 'name' => 'xx2'],
        ];
        $this->assertSame($expected, $this->unboxGridData($result));
    }

    public function testAppliesGrouping(): void
    {
        $tableData = [
            ['a' => '1', 'b' => '2', 'g' => 'a'],
            ['a' => '3', 'b' => '4', 'g' => 'c'],
            ['a' => '5', 'b' => '6', 'g' => 'b'],
            ['a' => '7', 'b' => '8', 'g' => 'a'],
        ];
        $this->createFixtureTable('foo', $tableData);

        $queryConfig = [
            'select' => [
                'from'    => ['table' => 'foo'],
                'columns' => [['column' => 'g'], ['expression' => 'COUNT(*)', '@as' => 'n']],
                'groupBy' => [['column' => 'g']],
            ],
        ];

        $sut = $this->createQueryGridSourceType($queryConfig);

        $result = $sut->fetchData(new SearchCriteria());
        $this->assertSame(3, $sut->extractTotalRowCount($result));
        $this->assertSame(
            [['g' => 'a', 'n' => '2'], ['g' => 'b', 'n' => '1'], ['g' => 'c', 'n' => '1']],
            $this->unboxGridData($result)
        );
    }

    public function testAppliesUnionSelectsAllByDefault(): void
    {
        $tableData1 = [
            ['a' => '1', 'b' => '2'],
            ['a' => '3', 'b' => '4'],
        ];
        $tableData2 = [
            ['a' => '1', 'b' => '2'],
            ['a' => 'x', 'b' => 'y'],
        ];
        $expected   = array_merge($tableData1, $tableData2);
        $this->createFixtureTable('foo1', $tableData1);
        $this->createFixtureTable('foo2', $tableData2);

        $queryConfig = [
            // implicit default: '@unionSelectType' => 'all',
            'select' => [
                'from'    => ['table' => 'foo1'],
                'columns' => [['column' => 'a'], ['column' => 'b']],
            ],
            'unions' => [
                [
                    'from'    => ['table' => 'foo2'],
                    'columns' => [['column' => 'a'], ['column' => 'b']],
                ],
            ],
        ];

        $sut = $this->createQueryGridSourceType($queryConfig);

        $result = $sut->fetchData(new SearchCriteria());
        $this->assertSame(4, $sut->extractTotalRowCount($result));
        $this->assertSame($expected, $this->unboxGridData($result));
    }

    public function testAppliesUnionSelectsDistinct(): void
    {
        $tableData1 = [
            ['a' => '1', 'b' => '2'],
            ['a' => '3', 'b' => '4'],
        ];
        $tableData2 = [
            ['a' => '1', 'b' => '2'], // this row is a duplicate from tableData1
            ['a' => 'x', 'b' => 'y'],
        ];
        $expected   = array_merge($tableData1, [['a' => 'x', 'b' => 'y']]);
        $this->createFixtureTable('foo1', $tableData1);
        $this->createFixtureTable('foo2', $tableData2);

        $queryConfig = [
            '@unionSelectType' => 'distinct',
            'select'           => [
                'from'    => ['table' => 'foo1'],
                'columns' => [['column' => 'a'], ['column' => 'b']],
            ],
            'unions'           => [
                [
                    'from'    => ['table' => 'foo2'],
                    'columns' => [['column' => 'a'], ['column' => 'b']],
                ],
            ],
        ];

        $sut = $this->createQueryGridSourceType($queryConfig);

        $result = $sut->fetchData(new SearchCriteria());
        $this->assertSame(4, $sut->extractTotalRowCount($result));
        $this->assertSame($expected, $this->unboxGridData($result));
    }

    public function testAppliesSearchCriteria(): void
    {
        $tableData = [
            ['a' => '4', 'b' => '2', 'c' => 1],
            ['a' => '6', 'b' => '2', 'c' => 1],
            ['a' => '5', 'b' => '4', 'c' => 1],
            ['a' => '3', 'b' => '4', 'c' => 1],
            ['a' => '2', 'b' => '5', 'c' => 1],
            ['a' => '1', 'b' => '2', 'c' => 0],
            ['a' => '1', 'b' => '2', 'c' => 1],
        ];
        $this->createFixtureTable('foo', $tableData);

        $queryConfig = [
            'select' => [
                'from'    => ['table' => 'foo'],
                'columns' => [['column' => 'a']],
            ],
        ];

        $sut = $this->createQueryGridSourceType($queryConfig);

        $searchCriteria = new SearchCriteria([
            'filter_groups' => [
                new FilterGroup([
                    'filters' => [
                        new Filter(['field' => 'b', 'value' => '2', 'condition_type' => 'eq']),
                        new Filter(['field' => 'b', 'value' => '5', 'condition_type' => 'eq']),
                    ],
                ]),
                new FilterGroup([
                    'filters' => [
                        new Filter(['field' => 'c', 'value' => '0', 'condition_type' => 'gt']),
                    ],
                ]),
            ],
            'sort_orders'   => [
                new SortOrder(['field' => 'a', 'direction' => 'asc']),
            ],
            'page_size'     => 3,
            'current_page'  => 1,
        ]);
        $result         = $sut->fetchData($searchCriteria);
        $this->assertSame(4, $sut->extractTotalRowCount($result));
        $this->assertSame([
            ['a' => '1'],
            ['a' => '2'],
            ['a' => '4'],
        ], $this->unboxGridData($result));
    }

    public function testExtractsTotalCountWhilePaging(): void
    {
        $tableData = [
            ['a' => '4', 'b' => '2'],
            ['a' => '6', 'b' => '2'],
            ['a' => '5', 'b' => '4'],
            ['a' => '3', 'b' => '4'],
            ['a' => '2', 'b' => '5'],
            ['a' => '1', 'b' => '2'],
            ['a' => '1', 'b' => '10'],
        ];
        $this->createFixtureTable('foo', $tableData);

        $queryConfig = [
            'select' => [
                'from' => ['table' => 'foo'],
            ],
        ];
        $sut         = $this->createQueryGridSourceType($queryConfig);

        $searchCriteria = new SearchCriteria([
            'page_size'    => 2,
            'current_page' => 4,
        ]);
        $result         = $sut->fetchData($searchCriteria);
        $this->assertSame(7, $sut->extractTotalRowCount($result));
        $this->assertSame([['a' => '1', 'b' => '10']], $this->unboxGridData($result));
    }

    public function testAppliesSourceProcessors(): void
    {
        $processor = new class() implements HyvaGridSourceProcessorInterface
        {
            /**
             * @param \Magento\Framework\DB\Select $source
             * @param SearchCriteriaInterface $searchCriteria
             * @param string $gridName
             */
            public function beforeLoad($source, SearchCriteriaInterface $searchCriteria, string $gridName): void
            {
                $source->columns('a');
            }

            public function afterLoad($rawResult, SearchCriteriaInterface $searchCriteria, string $gridName)
            {
                $rawResult['data'][0]['a'] = (string) ($rawResult['data'][0]['a'] + 1);

                return $rawResult;
            }
        };

        $tableData = [
            ['a' => '1', 'b' => '2'],
            ['a' => '3', 'b' => '4'],
        ];
        $this->createFixtureTable('foo', $tableData);

        $queryConfig = [
            'select' => [
                'from' => ['table' => 'foo'],
            ],
        ];

        $sut = $this->createQueryGridSourceType($queryConfig, [$processor]);

        $result = $sut->fetchData((new SearchCriteria())->setPageSize(1));
        $this->assertSame(2, $sut->extractTotalRowCount($result));
        $this->assertSame([['a' => '2', 'b' => '2']], $this->unboxGridData($result));
    }

    public function testFiltersJoinedFields(): void
    {
        $this->markTestIncomplete('TODO implement test. Not sure if problem. See issue #55 regarding db collections');
    }
}
