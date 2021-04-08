<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\GridSourceType;

use Hyva\Admin\Model\GridSourceType\QueryGridSourceType;
use Hyva\Admin\Model\GridSourceType\RawGridSourceDataAccessor;
use Hyva\Admin\Model\RawGridSourceContainer;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface as DbAdapter;
use Magento\Framework\DB\Ddl\Table;
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

    private function getConnection(): DbAdapter
    {
        return ObjectManager::getInstance()->get(ResourceConnection::class)->getConnection();
    }

    private function createFixtureTable(string $tableName, array $tableData): void
    {
        $this->dropTableIfExists($tableName);

        $firstRow    = values($tableData)[0];
        $columnNames = keys($firstRow);
        $types       = map(function ($v): string {
            return is_float($v) ? 'float' : (is_int($v) ? 'integer' : 'text');
        }, $firstRow);

        $table = new Table();
        $table->setName($tableName);

        map(function (string $columnName, string $type) use ($table): void {
            $table->addColumn($columnName, $type);
        }, $columnNames, $types);

        $this->getConnection()->createTable($table);
        $this->getConnection()->insertMultiple($tableName, $tableData);

        $this->fixtureTables[] = $tableName;
    }

    private function createQueryGridSourceType(array $queryConfig): QueryGridSourceType
    {
        $args = [
            'gridName'            => 'test',
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

    public function testAppliesUnionSelects(): void
    {
        $tableData1 = [
            ['a' => '1', 'b' => '2'],
            ['a' => '3', 'b' => '4'],
        ];
        $tableData2 = [
            ['a' => 'v', 'b' => 'w'],
            ['a' => 'x', 'b' => 'y'],
        ];
        $expected   = array_merge($tableData1, $tableData2);
        $this->createFixtureTable('foo1', $tableData1);
        $this->createFixtureTable('foo2', $tableData2);

        $queryConfig = [
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
}
