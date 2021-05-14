<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use function array_column as pick;
use function array_filter as filter;
use function array_map as map;
use function array_reduce as reduce;
use function array_search as search;

use Hyva\Admin\Api\HyvaGridSourceProcessorInterface;
use Hyva\Admin\Model\GridSourceType\QueryGridSourceType\DbSelectBuilder;
use Hyva\Admin\Model\GridSourceType\QueryGridSourceType\DbSelectEventContainer;
use Hyva\Admin\Model\RawGridSourceContainer;
use Hyva\Admin\Model\TypeReflection\DbSelectColumnExtractor;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\Search\FilterGroup;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface as EventManager;

class QueryGridSourceType implements GridSourceTypeInterface
{
    private const COLUMN_ALIASES = 2;

    /**
     * @var RawGridSourceDataAccessor
     */
    private $gridSourceDataAccessor;

    /**
     * @var ColumnDefinitionInterfaceFactory
     */
    private $columnDefinitionFactory;

    /**
     * @var DbSelectColumnExtractor
     */
    private $dbSelectColumnExtractor;

    /**
     * @var string
     */
    private $gridName;

    /**
     * @var array
     */
    private $sourceConfiguration;

    /**
     * @var DbSelectBuilder
     */
    private $dbSelectBuilder;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var HyvaGridSourceProcessorInterface[]
     */
    private $processors;

    public function __construct(
        string $gridName,
        array $sourceConfiguration,
        RawGridSourceDataAccessor $gridSourceDataAccessor,
        ColumnDefinitionInterfaceFactory $columnDefinitionFactory,
        DbSelectColumnExtractor $dbSelectColumnExtractor,
        DbSelectBuilder $dbSelectBuilder,
        EventManager $eventManager,
        array $processors = []
    ) {
        $this->gridName                = $gridName;
        $this->sourceConfiguration     = $sourceConfiguration['query'] ?? [];
        $this->processors              = $processors;
        $this->gridSourceDataAccessor  = $gridSourceDataAccessor;
        $this->columnDefinitionFactory = $columnDefinitionFactory;
        $this->dbSelectColumnExtractor = $dbSelectColumnExtractor;
        $this->dbSelectBuilder         = $dbSelectBuilder;
        $this->eventManager            = $eventManager;
    }

    public function getColumnKeys(): array
    {
        return $this->dbSelectColumnExtractor->getSelectColumns($this->getSelect());
    }

    public function getColumnDefinition(string $key): ColumnDefinitionInterface
    {
        return $this->columnDefinitionFactory->create([
            'key'  => $key,
            'type' => $this->mapDbTypeToColumnType($this->dbSelectColumnExtractor->getColumnType(
                $this->getSelect(),
                $key
            )),
        ]);
    }

    private function mapDbTypeToColumnType(string $dbType): string
    {
        /**
         * @see \Magento\Framework\DB\Adapter\Pdo\Mysql::$_ddlColumnTypes
         *
         * There also are a couple of other db types in the map that are used by the core.
         * Regarding casting floats and decimals to price type, that is the most common case
         * in Magento, so I figured it probably requires the least number of overrides in
         * grid configurations.
         */
        $dbTypeToColumnTypeMap = [
            'boolean'    => 'bool',
            'smallint'   => 'int',
            'integer'    => 'int',
            'bigint'     => 'int',
            'float'      => 'price',
            'decimal'    => 'price',
            'varchar'    => 'string',
            'shorttext'  => 'string',
            'char'       => 'string',
            'mediumtext' => 'text',
            'longtext'   => 'text',
            'timestamp'  => 'datetime',
            'date'       => 'datetime',
        ];

        return $dbTypeToColumnTypeMap[$dbType] ?? $dbType;
    }

    public function fetchData(SearchCriteriaInterface $searchCriteria): RawGridSourceContainer
    {
        $select = $this->prepareSelect($searchCriteria);

        $db = $select->getConnection();
        // Call query() on the adapter instead of $select because the atter drops the bind parameters (bug).
        $data        = $db->query($select, $select->getBind())->fetchAll();
        $countSelect = $this->getSelectCountSql($select);
        $count       = (int) $db->query($countSelect, $countSelect->getBind())->fetchColumn();

        $rawGridSourceData = reduce(
            $this->processors,
            function (array $sourceData, HyvaGridSourceProcessorInterface $processor) use ($searchCriteria): array {
                return $processor->afterLoad($sourceData, $searchCriteria, $this->gridName) ?? $sourceData;
            },
            ['data' => $data, 'count' => $count]
        );

        return RawGridSourceContainer::forData($rawGridSourceData);
    }

    public function getRecordType(): string
    {
        return 'array';
    }

    public function extractRecords(RawGridSourceContainer $rawGridData): array
    {
        return $this->gridSourceDataAccessor->unbox($rawGridData)['data'];
    }

    public function extractValue($record, string $key)
    {
        return $record[$key] ?? null;
    }

    public function extractTotalRowCount(RawGridSourceContainer $rawGridData): int
    {
        return $this->gridSourceDataAccessor->unbox($rawGridData)['count'];
    }

    private function getSelect(): Select
    {
        return $this->dbSelectBuilder->forConfig($this->sourceConfiguration);
    }

    /**
     * @see \Magento\Framework\Data\Collection\AbstractDb::getSelectCountSql
     */
    private function getSelectCountSql(Select $select): Select
    {
        if ($unionSelects = $select->getPart(Select::UNION)) {
            $unionCountSelecstPart = map(function (array $unionPart) {
                $countSelect = $this->getSelectCountSql($unionPart[0]);
                return [$countSelect, Select::SQL_UNION_ALL];
            }, $unionSelects);
            $countSelect           = clone $select;
            $countSelect->setPart(Select::UNION, $unionCountSelecstPart);

            $sumCountsSelect = $select->getConnection()->select();
            $sumCountsSelect->from($countSelect, new \Zend_Db_Expr('SUM(n)'));

            return $sumCountsSelect;
        }
        return $this->buildCountSelect($select);
    }

    private function buildCountSelect(Select $select): Select
    {
        $countSelect = clone $select;
        $countSelect->reset(Select::ORDER);
        $countSelect->reset(Select::LIMIT_COUNT);
        $countSelect->reset(Select::LIMIT_OFFSET);
        $countSelect->reset(Select::COLUMNS);

        $part = $this->getSelect()->getPart(Select::GROUP);
        if (!is_array($part) || !count($part)) {
            $countSelect->columns(['n' => new \Zend_Db_Expr('COUNT(*)')]);
            return $countSelect;
        }

        $countSelect->reset(Select::GROUP);
        $group = $this->getSelect()->getPart(Select::GROUP);
        $countSelect->columns(new \Zend_Db_Expr(("COUNT(DISTINCT " . implode(", ", $group) . ")")));

        return $countSelect;
    }

    private function applyFilters(Select $select, SearchCriteriaInterface $searchCriteria): void
    {
        $filterGroupsSql = map(function (FilterGroup $group) use ($select): string {
            $filtersSql = map(function (Filter $filter) use ($select): string {
                $condition = [$filter->getConditionType() ?? 'eq' => $filter->getValue()];
                $fieldName = $this->extractRealColumnNameForAlias($select, $filter->getField());
                return $select->getConnection()->prepareSqlCondition($fieldName, $condition);
            }, $group->getFilters());
            return implode(' OR ', $filtersSql);
        }, $searchCriteria->getFilterGroups());

        map([$select, 'where'], $filterGroupsSql);
    }

    private function applySortOrder(Select $select, SearchCriteriaInterface $searchCriteria): void
    {
        $orderSpecs = filter(map(function (SortOrder $sortOrder) use ($select): string {
            $fieldName = $this->extractRealColumnNameForAlias($select, $sortOrder->getField());
            return $fieldName
                ? sprintf('%s %s', $fieldName, $sortOrder->getDirection())
                : '';
        }, $searchCriteria->getSortOrders() ?? []));

        $select->order($orderSpecs);
    }

    private function applyPagination(Select $select, SearchCriteriaInterface $searchCriteria): void
    {
        if ($pageSize = $searchCriteria->getPageSize()) {
            $page = max($searchCriteria->getCurrentPage() ?? 1, 1);
            $select->limitPage($page, $pageSize);
        }
    }

    private function dispatchQueryBeforeEvent(Select $select): Select
    {
        $event           = 'hyva_grid_query_before_' . $this->getGridNameEventSuffix($this->gridName);
        $selectContainer = new DbSelectEventContainer($select);
        $this->eventManager->dispatch($event, [
            'select_container' => $selectContainer,
            'grid_name'        => $this->gridName,
        ]);

        return $selectContainer->getSelect();
    }

    private function getGridNameEventSuffix(string $gridName): string
    {
        return strtolower(preg_replace('/[^[:alpha:]]+/', '_', $gridName));
    }

    private function prepareSelect(SearchCriteriaInterface $searchCriteria): Select
    {
        $select = $this->getSelect();

        $this->applyFilters($select, $searchCriteria);
        $this->applySortOrder($select, $searchCriteria);
        $this->applyPagination($select, $searchCriteria);

        return $this->dispatchQueryBeforeEvent(reduce(
            $this->processors,
            function (Select $select, HyvaGridSourceProcessorInterface $processor) use ($searchCriteria): Select {
                $processor->beforeLoad($select, $searchCriteria, $this->gridName);
                return $select;
            },
            $select
        ));
    }

    private function extractRealColumnNameForAlias(Select $select, ?string $maybeAlias): ?string
    {
        if (!$maybeAlias) {
            return null;
        }
        $aliases = pick($select->getPart(Select::COLUMNS), self::COLUMN_ALIASES);
        $idx     = search($maybeAlias, $aliases, true);
        [$schema, $column] = $select->getPart(Select::COLUMNS)[$idx];
        $expression = is_string($column) ? // might be Zend_Db_Expression
            $schema . '.' . $column
            : (string) $column;

        return $idx !== false  && is_string($column) ? $expression : $maybeAlias;
    }

    public function getGridName(): string
    {
        return $this->gridName;
    }
}
