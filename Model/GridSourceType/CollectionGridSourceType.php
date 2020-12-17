<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use Hyva\Admin\Model\GridSourceType\CollectionSourceType\GridSourceCollectionFactory;
use Hyva\Admin\Model\GridSourceType\Internal\RawGridSourceDataAccessor;
use Hyva\Admin\Model\RawGridSourceContainer;
use Hyva\Admin\Model\TypeReflection;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaInterface;

class CollectionGridSourceType implements GridSourceTypeInterface
{
    private string $gridName;

    private array $sourceConfiguration;

    private TypeReflection $typeReflection;

    private RawGridSourceDataAccessor $gridSourceDataAccessor;

    private ColumnDefinitionInterfaceFactory $columnDefinitionFactory;

    private GridSourceCollectionFactory $gridSourceCollectionFactory;

    public function __construct(
        string $gridName,
        array $sourceConfiguration,
        TypeReflection $typeReflection,
        RawGridSourceDataAccessor $gridSourceDataAccessor,
        ColumnDefinitionInterfaceFactory $columnDefinitionFactory,
        GridSourceCollectionFactory $gridSourceCollectionFactory
    ) {
        $this->gridName                    = $gridName;
        $this->sourceConfiguration         = $sourceConfiguration;
        $this->typeReflection              = $typeReflection;
        $this->gridSourceDataAccessor      = $gridSourceDataAccessor;
        $this->columnDefinitionFactory     = $columnDefinitionFactory;
        $this->gridSourceCollectionFactory = $gridSourceCollectionFactory;
    }

    private function getCollectionConfig(): string
    {
        return $this->sourceConfiguration['collection'] ?? '';
    }

    public function getColumnKeys(): array
    {
        $collection = $this->gridSourceCollectionFactory->create($this->getCollectionConfig());
        $entityClass = $collection->getItemObjectClass();

        return $this->typeReflection->getFieldNames($entityClass);
    }

    public function getColumnDefinition(string $key): ColumnDefinitionInterface
    {

    }

    public function fetchData(SearchCriteriaInterface $searchCriteria): RawGridSourceContainer
    {

    }

    public function extractRecords(RawGridSourceContainer $rawGridData): array
    {

    }

    public function extractValue($record, string $key)
    {

    }

    public function extractTotalRowCount(RawGridSourceContainer $rawGridData): int
    {

    }
}
