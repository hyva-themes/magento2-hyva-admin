<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType\QueryGridSourceType;

use function array_column as pick;
use function array_filter as filter;
use function array_map as map;
use function array_merge as merge;
use function array_reduce as reduce;
use function array_values as values;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class DbSelectBuilder
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    public function forConfig(array $queryConfig): Select
    {
        $select = $this->buildSelect($queryConfig['select'] ?? []);

        return $this->applyUnionSelects($select, $queryConfig['unions'] ?? []);
    }

    private function buildSelect(array $selectConfig): Select
    {
        $select  = $this->resourceConnection->getConnection()->select();
        $table   = $this->buildTableArg($selectConfig['from'] ?? []);
        $columns = $this->buildColumnsArg($selectConfig['columns'] ?? []) ?: '*';
        $select->from($table, $columns);
        $select->group(pick($selectConfig['groupBy'] ?? [], 'column'));

        return reduce($selectConfig['joins'] ?? [], [$this, 'applyJoinConfig'], $select);
    }

    private function applyJoinConfig(Select $select, array $joinConfig): Select
    {
        $joinMethod = 'join' . ucfirst($joinConfig['type'] ?? 'left');
        $table      = $this->buildTableArg($joinConfig['join'] ?? []);
        $columns    = $this->buildColumnsArg($joinConfig['columns'] ?? []);
        $select->$joinMethod($table, $joinConfig['on'] ?? '', $columns);

        return $select;
    }

    private function buildColumnsArg(array $columnsConfig): array
    {
        return merge([], ...values(map([$this, 'buildColumnArg'], $columnsConfig)));
    }

    /**
     * @param array $columnConfig
     * @return string|string[]
     */
    private function buildColumnArg(array $columnConfig): array
    {
        $expression = isset($columnConfig['expression']) ? new \Zend_Db_Expr($columnConfig['expression']) : '';
        $column     = $columnConfig['column'] ?? $expression;

        return isset($columnConfig['@as'])
            ? [$columnConfig['@as'] => $column]
            : [$column];
    }

    /**
     * @param array $fromConfig
     * @return string|string[]
     */
    private function buildTableArg(array $fromConfig)
    {
        $table = $fromConfig['table'] ?? '';

        return isset($fromConfig['@as'])
            ? [$fromConfig['@as'] => $table]
            : $table;
    }

    private function applyUnionSelects(Select $select, array $unionSelectsConfig): Select
    {
        $select = $this->applyUnionSelectDistinct($select, $unionSelectsConfig);

        return $this->applyUnionSelectAll($select, $unionSelectsConfig);
    }

    private function applyUnionSelectDistinct(Select $select, array $unionSelectsConfig): Select
    {
        $distinctUnionSelectsConfig = filter($unionSelectsConfig ?? [], function (array $unionConfig): bool {
            return ($unionConfig['type'] ?? 'distinct') !== 'all';
        });
        return $this->applyUnionSelectConfig($select, $distinctUnionSelectsConfig, Select::SQL_UNION);
    }

    private function applyUnionSelectAll(Select $select, array $unionSelectsConfig): Select
    {
        $allUnionSelectsConfig = filter($unionSelectsConfig ?? [], function (array $unionConfig): bool {
            return ($unionConfig['type'] ?? 'distinct') === 'all';
        });
        return $this->applyUnionSelectConfig($select, $allUnionSelectsConfig, Select::SQL_UNION_ALL);
    }

    private function applyUnionSelectConfig(Select $select, array $unionSelectConfigs, string $unionType): Select
    {
        $selects = map([$this, 'buildSelect'], $unionSelectConfigs);
        return $selects
            ? $select->getConnection()->select()->union(merge([$select], $selects), $unionType)
            : $select;
    }
}
