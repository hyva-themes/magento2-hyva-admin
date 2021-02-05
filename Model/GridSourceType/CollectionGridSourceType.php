<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use Hyva\Admin\Model\GridSourcePrefetchEventDispatcher;
use Hyva\Admin\Model\GridSourceType\CollectionSourceType\GridSourceCollectionFactory;
use Hyva\Admin\Model\GridSourceType\RawGridSourceDataAccessor;
use Hyva\Admin\Model\RawGridSourceContainer;
use Hyva\Admin\Model\TypeReflection;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;
use Magento\Eav\Model\Entity\Collection\AbstractCollection as AbstractEavCollection;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

use function array_filter as filter;
use function array_values as values;

class CollectionGridSourceType implements GridSourceTypeInterface
{
    /**
     * @var mixed[]
     */
    private $sourceConfiguration;

    /**
     * @var \Hyva\Admin\Model\TypeReflection
     */
    private $typeReflection;

    /**
     * @var \Hyva\Admin\Model\GridSourceType\RawGridSourceDataAccessor
     */
    private $gridSourceDataAccessor;

    /**
     * @var \Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory
     */
    private $columnDefinitionFactory;

    /**
     * @var \Hyva\Admin\Model\GridSourceType\CollectionSourceType\GridSourceCollectionFactory
     */
    private $gridSourceCollectionFactory;

    /**
     * @var \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface
     */
    private $defaultCollectionProcessor;

    /**
     * @var \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface
     */
    private $eavCollectionProcessor;

    public function __construct(
        array $sourceConfiguration,
        TypeReflection $typeReflection,
        RawGridSourceDataAccessor $gridSourceDataAccessor,
        ColumnDefinitionInterfaceFactory $columnDefinitionFactory,
        GridSourceCollectionFactory $gridSourceCollectionFactory,
        CollectionProcessorInterface $defaultCollectionProcessor,
        CollectionProcessorInterface $eavCollectionProcessor
    ) {
        $this->sourceConfiguration         = $sourceConfiguration;
        $this->typeReflection              = $typeReflection;
        $this->gridSourceDataAccessor      = $gridSourceDataAccessor;
        $this->columnDefinitionFactory     = $columnDefinitionFactory;
        $this->gridSourceCollectionFactory = $gridSourceCollectionFactory;
        $this->defaultCollectionProcessor  = $defaultCollectionProcessor;
        $this->eavCollectionProcessor      = $eavCollectionProcessor;
    }

    public function getRecordType(): string
    {
        $collection = $this->gridSourceCollectionFactory->create($this->getCollectionConfig());
        $type       = $collection->getItemObjectClass();
        return $type === \Magento\Framework\View\Element\UiComponent\DataProvider\Document::class
            ? $collection->getMainTable() // seems to work okay for now
            : $type;
    }

    private function getCollectionConfig(): string
    {
        return $this->sourceConfiguration['collection'] ?? '';
    }

    public function getColumnKeys(): array
    {
        return $this->typeReflection->getFieldNames($this->getRecordType());
    }

    public function getColumnDefinition(string $key): ColumnDefinitionInterface
    {
        return $this->buildColumnDefinition($key);
    }

    private function buildColumnDefinition(string $key): ColumnDefinitionInterface
    {
        $recordType = $this->getRecordType();
        $columnType = $this->typeReflection->getColumnType($recordType, $key);
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

    private function isNonSortableColumn(): bool
    {
        // Implement this as needed
        return false;
    }

    public function fetchData(SearchCriteriaInterface $searchCriteria): RawGridSourceContainer
    {
        $collection = $this->gridSourceCollectionFactory->create($this->getCollectionConfig());
        if (method_exists($collection, 'addFieldToSelect')) {
            $collection->addFieldToSelect('*');
        }

        if (is_subclass_of($collection, AbstractEavCollection::class)) {
            $this->eavCollectionProcessor->process($searchCriteria, $collection);
        } else {
            $this->defaultCollectionProcessor->process($searchCriteria, $collection);
        }

        return RawGridSourceContainer::forData($collection);
    }

    public function extractRecords(RawGridSourceContainer $rawGridData): array
    {
        return values($this->gridSourceDataAccessor->unbox($rawGridData)->getItems());
    }

    public function extractValue($record, string $key)
    {
        return $this->typeReflection->extractValue($this->getRecordType(), $key, $record);
    }

    public function extractTotalRowCount(RawGridSourceContainer $rawGridData): int
    {
        return $this->gridSourceDataAccessor->unbox($rawGridData)->getSize();
    }
}
