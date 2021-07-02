<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use function array_filter as filter;
use function array_map as map;
use function array_merge as merge;
use function array_reduce as reduce;
use function array_unique as unique;
use function array_values as values;

use Hyva\Admin\Api\HyvaGridCollectionProcessorInterface;
use Hyva\Admin\Api\HyvaGridSourceProcessorInterface;
use Hyva\Admin\Model\GridSourceType\CollectionSourceType\GridSourceCollectionFactory;
use Hyva\Admin\Model\GridTypeReflection;
use Hyva\Admin\Model\RawGridSourceContainer;
use Hyva\Admin\Model\TypeReflection\DbSelectColumnExtractor;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;
use Magento\Eav\Model\Entity\Collection\AbstractCollection as AbstractEavCollection;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\Collection\AbstractDb;

class CollectionGridSourceType implements GridSourceTypeInterface
{
    /**
     * @var string
     */
    private $gridName;

    /**
     * @var array[]
     */
    private $sourceConfiguration;

    /**
     * @var GridTypeReflection
     */
    private $typeReflection;

    /**
     * @var RawGridSourceDataAccessor
     */
    private $gridSourceDataAccessor;

    /**
     * @var ColumnDefinitionInterfaceFactory
     */
    private $columnDefinitionFactory;

    /**
     * @var GridSourceCollectionFactory
     */
    private $gridSourceCollectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $defaultCollectionProcessor;

    /**
     * @var CollectionProcessorInterface
     */
    private $eavCollectionProcessor;

    /**
     * @var DbSelectColumnExtractor
     */
    private $dbSelectColumnExtractor;

    /**
     * @var string[]
     */
    private $memoizedTypeReflectionFields;

    /**
     * @var string[]
     */
    private $memoizedSelectInspectionFields;

    /**
     * @var HyvaGridSourceProcessorInterface
     */
    private $processors;

    public function __construct(
        string $gridName,
        array $sourceConfiguration,
        GridTypeReflection $typeReflection,
        DbSelectColumnExtractor $dbSelectColumnExtractor,
        RawGridSourceDataAccessor $gridSourceDataAccessor,
        ColumnDefinitionInterfaceFactory $columnDefinitionFactory,
        GridSourceCollectionFactory $gridSourceCollectionFactory,
        CollectionProcessorInterface $defaultCollectionProcessor,
        CollectionProcessorInterface $eavCollectionProcessor,
        array $processors = []
    ) {
        $this->gridName                    = $gridName;
        $this->sourceConfiguration         = $sourceConfiguration;
        $this->processors                  = $processors;
        $this->typeReflection              = $typeReflection;
        $this->dbSelectColumnExtractor     = $dbSelectColumnExtractor;
        $this->gridSourceDataAccessor      = $gridSourceDataAccessor;
        $this->columnDefinitionFactory     = $columnDefinitionFactory;
        $this->gridSourceCollectionFactory = $gridSourceCollectionFactory;
        $this->defaultCollectionProcessor  = $defaultCollectionProcessor;
        $this->eavCollectionProcessor      = $eavCollectionProcessor;
    }

    public function getRecordType(): string
    {
        return $this->getCollectionInstance()->getItemObjectClass();
    }

    private function getCollectionConfig(): string
    {
        return $this->sourceConfiguration['collection'] ?? '';
    }

    public function getColumnKeys(): array
    {
        $this->memoizeFieldNames();
        return unique(merge($this->memoizedTypeReflectionFields, $this->memoizedSelectInspectionFields));
    }

    public function getColumnDefinition(string $key): ColumnDefinitionInterface
    {
        $this->memoizeFieldNames();
        return $this->buildColumnDefinition($key);
    }

    private function buildColumnDefinition(string $key): ColumnDefinitionInterface
    {
        $recordType = $this->getRecordType();
        $columnType = $this->isTypeReflectionField($key)
            ? $this->typeReflection->getColumnType($recordType, $key)
            : $this->dbSelectColumnExtractor->getColumnType($this->getCollectionInstance()->getSelect(), $key);
        $label      = $this->typeReflection->extractLabel($recordType, $key);
        $options    = $this->typeReflection->extractOptions($recordType, $key);

        $sortable = $this->isNonSortableColumn($key, $recordType, $columnType)
            ? 'false'
            : null;

        $constructorArguments = filter([
            'key'      => $key,
            'type'     => $columnType,
            'label'    => $label,
            'options'  => $options,
            'sortable' => $sortable,
        ]);

        return $this->columnDefinitionFactory->create($constructorArguments);
    }

    private function isNonSortableColumn(string $key, string $recordType, string $columnType): bool
    {
        // Implement this as needed
        return false;
    }

    public function fetchData(SearchCriteriaInterface $searchCriteria): RawGridSourceContainer
    {
        $collection = $this->getCollectionInstance();

        map(function (HyvaGridSourceProcessorInterface $processor) use ($collection, $searchCriteria): void {
            $processor->beforeLoad($collection, $searchCriteria, $this->gridName);
        }, $this->processors);

        if (method_exists($collection, 'addFieldToSelect')) {
            $collection->addFieldToSelect('*');
        }

        if (is_subclass_of($collection, AbstractEavCollection::class)) {
            /**
             * Todo: fix filtering joined attributes
             */
            $this->eavCollectionProcessor->process($searchCriteria, $collection);
        } else {
            /**
             * Todo: map aliases to real field names in the filter conditions.
             */
            $this->defaultCollectionProcessor->process($searchCriteria, $collection);
        }

        $afterProcessdCollection = reduce(
            $this->processors,
            function (
                AbstractDb $collection,
                HyvaGridSourceProcessorInterface $processor
            ) use ($searchCriteria): AbstractDb {
                return $processor->afterLoad($collection, $searchCriteria, $this->gridName) ?? $collection;
            },
            $collection
        );

        return RawGridSourceContainer::forData($afterProcessdCollection);
    }

    public function extractRecords(RawGridSourceContainer $rawGridData): array
    {
        return values($this->gridSourceDataAccessor->unbox($rawGridData)->getItems());
    }

    public function extractValue($record, string $key)
    {
        $this->memoizeFieldNames();
        return $this->isTypeReflectionField($key)
            ? $this->typeReflection->extractValue($this->getRecordType(), $key, $record)
            : $this->dbSelectColumnExtractor->extractColumnValue($key, $record);
    }

    public function extractTotalRowCount(RawGridSourceContainer $rawGridData): int
    {
        return $this->gridSourceDataAccessor->unbox($rawGridData)->getSize();
    }

    private function getCollectionInstance(): AbstractDb
    {
        return reduce(
            $this->processors,
            function (AbstractDb $collection, HyvaGridSourceProcessorInterface $processor): AbstractDb {
                if ($processor instanceof HyvaGridCollectionProcessorInterface) {
                    $processor->afterInitSelect($collection, $this->gridName);
                }
                return $collection;
            },
            $this->gridSourceCollectionFactory->create($this->getCollectionConfig()));
    }

    private function isTypeReflectionField(string $key): bool
    {
        return in_array($key, $this->memoizedTypeReflectionFields, true);
    }

    private function memoizeFieldNames(): void
    {
        if (!isset($this->memoizedTypeReflectionFields)) {
            $type                               = $this->getRecordType();
            $this->memoizedTypeReflectionFields = $this->typeReflection->getFieldNames($type);

            $select                               = $this->getCollectionInstance()->getSelect();
            $this->memoizedSelectInspectionFields = $this->dbSelectColumnExtractor->getSelectColumns($select);
        }
    }

    public function getGridName(): string
    {
        return $this->gridName;
    }
}
