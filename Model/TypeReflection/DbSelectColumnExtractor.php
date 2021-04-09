<?php declare(strict_types=1);

namespace Hyva\Admin\Model\TypeReflection;

use Magento\Framework\DB\Select;

use function array_filter as filter;
use function array_map as map;
use function array_merge as merge;
use function array_values as values;

class DbSelectColumnExtractor
{
    /**
     * @var TableColumnExtractor
     */
    private $tableColumnExtractor;

    public function __construct(TableColumnExtractor $tableColumnExtractor)
    {
        $this->tableColumnExtractor = $tableColumnExtractor;
    }

    private function getFullColumnList(Select $select): array
    {
        return merge(...values(map(function (array $columnEntry) use ($select): array {
            [$correlationName, $column, $alias] = $columnEntry;
            return $column === '*'
                ? $this->expandAsterixToAllTableColumns($this->getRealTableNameIfAlias($select, $correlationName))
                : [$columnEntry];
        }, $select->getPart(Select::COLUMNS))));
    }

    private function getRealTableNameIfAlias(Select $select, string $aliasOrTableName): string
    {
        $fromParts = $select->getPart(Select::FROM);
        return isset($fromParts[$aliasOrTableName])
            ? $fromParts[$aliasOrTableName]['tableName']
            : $aliasOrTableName;
    }

    private function expandAsterixToAllTableColumns(string $tableName): array
    {
        return map(function (string $column) use ($tableName): array {
            return [$tableName, $column, /* alias */ null];
        }, $this->tableColumnExtractor->getTableColumns($tableName));
    }

    public function getSelectColumns(Select $select): array
    {
        return values(map(function (array $columnEntry): string {
            [$correlationName, $column, $alias] = $columnEntry;
            return $alias ?: $column;
        }, $this->getFullColumnList($select)));
    }

    public function getColumnType(Select $select, string $key): string
    {
        $columnEntries = filter($this->getFullColumnList($select), function (array $columnEntry) use ($key): bool {
            [$correlationName, $column, $alias] = $columnEntry;
            return $key === ($alias ?: $column);
        });
        return $columnEntries
            ? $this->extractColumnType($select, values($columnEntries)[0])
            : 'unknown';

    }

    private function extractColumnType(Select $select, array $columnEntry): ?string
    {
        [$correlationName, $column, $alias] = $columnEntry;
        $tableName = $this->getRealTableNameIfAlias($select, $correlationName);

        return is_string($column)
            ? $this->tableColumnExtractor->getColumnType($tableName, $column)
            : 'string'; // for Zend_Db_Expr instances default to string type
    }

    public function extractColumnValue(string $key, $object)
    {
        if (method_exists($object, 'getData')) {
            return $object->getData($key);
        }
        if ($object instanceof \ArrayAccess) {
            return $object[$key];
        }
        return null;
    }
}
