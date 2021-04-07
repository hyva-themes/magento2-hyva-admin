<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType\QueryGridSourceType;

use function array_column as pick;
use function array_filter as filter;
use function array_map as map;
use function array_reduce as reduce;

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

        return $this->applyUnionSelectConfig($select, $queryConfig['unions'] ?? []);
    }

    private function buildSelect(array $selectConfig): Select
    {
        $select  = $this->resourceConnection->getConnection()->select();
        $table   = $this->buildTableArg($selectConfig['from'] ?? []);
        $columns = map([$this, 'buildColumnArg'], $selectConfig['columns'] ?? []) ?: '*';
        $select->from($table, $columns);
        $select->group(pick($selectConfig['groupBy'] ?? [], 'column'));

        return reduce($selectConfig['joins'] ?? [], [$this, 'applyJoinConfig'], $select);
    }

    private function applyJoinConfig(Select $select, array $joinConfig): Select
    {
        $joinMethod = 'join' . ucfirst($joinConfig['type'] ?? 'left');
        $table      = $this->buildTableArg($joinConfig['join'] ?? []);
        $columns    = map([$this, 'buildColumnArg'], $joinConfig['columns'] ?? []) ?: [];
        $select->$joinMethod($table, $joinConfig['on'] ?? '', $columns);

        return $select;
    }

    /**
     * @param array $columnConfig
     * @return string|string[]
     */
    private function buildColumnArg(array $columnConfig)
    {
        $column = $columnConfig['column'] ?? '';

        return isset($columnConfig['@as'])
            ? [$columnConfig['@as'] => $column]
            : $column;
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

    private function applyUnionSelectConfig(Select $select, array $unionSelectsConfig): Select
    {
        $distinctUnionSelectConfigs = filter($unionSelectsConfig ?? [], function (array $unionConfig): bool {
            return ($unionConfig['type'] ?? 'distinct') !== 'all';
        });
        $select->union(map([$this, 'buildSelect'], $distinctUnionSelectConfigs), Select::SQL_UNION);

        $allUnionSelectConfigs = filter($unionSelectsConfig ?? [], function (array $unionConfig): bool {
            return ($unionConfig['type'] ?? 'distinct') === 'all';
        });
        $select->union(map([$this, 'buildSelect'], $allUnionSelectConfigs), Select::SQL_UNION_ALL);

        return $select;
    }
}
