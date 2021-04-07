<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use Hyva\Admin\Model\GridSourceType\QueryGridSourceType\DbSelectBuilder;
use Hyva\Admin\Model\RawGridSourceContainer;
use Hyva\Admin\Model\TypeReflection\DbSelectColumnExtractor;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\DB\Select;

class QueryGridSourceType implements GridSourceTypeInterface
{

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

    public function __construct(
        string $gridName,
        array $sourceConfiguration,
        RawGridSourceDataAccessor $gridSourceDataAccessor,
        ColumnDefinitionInterfaceFactory $columnDefinitionFactory,
        DbSelectColumnExtractor $dbSelectColumnExtractor,
        DbSelectBuilder $dbSelectBuilder
    ) {
        $this->gridName                = $gridName;
        $this->sourceConfiguration     = $sourceConfiguration['query'] ?? [];
        $this->gridSourceDataAccessor  = $gridSourceDataAccessor;
        $this->columnDefinitionFactory = $columnDefinitionFactory;
        $this->dbSelectColumnExtractor = $dbSelectColumnExtractor;
        $this->dbSelectBuilder         = $dbSelectBuilder;
    }

    public function getColumnKeys(): array
    {
        return $this->dbSelectColumnExtractor->getSelectColumns($this->getSelect());
    }

    public function getColumnDefinition(string $key): ColumnDefinitionInterface
    {
        return $this->columnDefinitionFactory->create([
            'key'  => $key,
            'type' => $this->dbSelectColumnExtractor->getColumnType($this->getSelect(), $key),
        ]);
    }

    public function fetchData(SearchCriteriaInterface $searchCriteria): RawGridSourceContainer
    {
        $select = $this->getSelect();

        // todo: apply search criteria

        // todo: dispatch query_before event with select instance

        $data  = $select->query(\Zend_Db::FETCH_ASSOC);
        $count = (int) $this->getSelectCountSql($select)->query(\Zend_db::FETCH_NUM)[0];

        return RawGridSourceContainer::forData(['data' => $data, 'count' => $count]);
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
        $countSelect = clone $select;
        $countSelect->reset(Select::ORDER);
        $countSelect->reset(Select::LIMIT_COUNT);
        $countSelect->reset(Select::LIMIT_OFFSET);
        $countSelect->reset(Select::COLUMNS);

        $part = $this->getSelect()->getPart(Select::GROUP);
        if (!is_array($part) || !count($part)) {
            $countSelect->columns(new \Zend_Db_Expr('COUNT(*)'));
            return $countSelect;
        }

        $countSelect->reset(Select::GROUP);
        $group = $this->getSelect()->getPart(Select::GROUP);
        $countSelect->columns(new \Zend_Db_Expr(("COUNT(DISTINCT " . implode(", ", $group) . ")")));

        return $countSelect;
    }
}
