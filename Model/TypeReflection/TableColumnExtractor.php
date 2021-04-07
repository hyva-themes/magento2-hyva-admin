<?php declare(strict_types=1);

namespace Hyva\Admin\Model\TypeReflection;

use Magento\Framework\App\ResourceConnection;

use function array_keys as keys;

class TableColumnExtractor
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var array[]
     */
    private $memoizedTableDescriptions = [];

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    private function describeTable(string $tableName): array
    {
        if (! isset($this->memoizedTableDescriptions[$tableName])) {
            $connection = $this->resourceConnection->getConnection();
            $this->memoizedTableDescriptions[$tableName] = $connection->isTableExists($tableName)
                ? $connection->describeTable($tableName)
                : [];
        }
        return $this->memoizedTableDescriptions[$tableName];
    }

    public function getTableColumns(string $tableName): array
    {
        return keys($this->describeTable($tableName));
    }

    public function getColumnType(string $tableName, string $key): ?string
    {
        $tableDescription = $this->describeTable($tableName);
        return isset($tableDescription[$key])
            ? $tableDescription[$key]['DATA_TYPE']
            : null;
    }

    public function extractColumnValue(string $type, string $key, $object)
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
