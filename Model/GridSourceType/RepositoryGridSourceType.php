<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use Hyva\Admin\Api\DataTypeGuesserInterface;
use Hyva\Admin\Model\DataType\ArrayDataType;
use Hyva\Admin\Model\DataType\ProductGalleryDataType;
use Hyva\Admin\Model\GridSourceType\RepositorySourceType\CustomAttributesExtractor;
use Hyva\Admin\Model\GridSourceType\RepositorySourceType\ExtensionAttributeTypeExtractor;
use Hyva\Admin\Model\GridSourceType\RepositorySourceType\GetterMethodsExtractor;
use Hyva\Admin\Model\GridSourceType\Internal\RawGridSourceDataAccessor;
use Hyva\Admin\Model\GridSourceType\RepositorySourceType\RepositorySourceFactory;
use Hyva\Admin\Model\GridSourceType\RepositorySourceType\SearchCriteriaEventContainer;
use Hyva\Admin\Model\RawGridSourceContainer;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\Event\ManagerInterface;

use function array_filter as filter;
use function array_merge as merge;
use function array_unique as unique;

class RepositoryGridSourceType implements GridSourceTypeInterface
{

    private string $gridName;

    private array $sourceConfiguration;

    private RawGridSourceDataAccessor $gridSourceDataAccessor;

    private RepositorySourceFactory $repositorySourceFactory;

    private GetterMethodsExtractor $getterMethodsExtractor;

    private ExtensionAttributeTypeExtractor $extensionAttributeTypeExtractor;

    private CustomAttributesExtractor $customAttributesExtractor;

    private ColumnDefinitionInterfaceFactory $columnDefinitionFactory;

    private SearchCriteriaBuilder $searchCriteriaBuilder;

    private DataTypeGuesserInterface $dataTypeGuesser;

    private ManagerInterface $eventManager;

    /**
     * @var string[]
     */
    private array $getMethodKeys;

    /**
     * @var string[]
     */
    private array $extensionAttributeKeys;

    /**
     * @var string[]
     */
    private array $customAttributeKeys;

    /**
     * @var ColumnDefinitionInterface[]
     */
    private $memoizedColumnDefinitions = [];

    public function __construct(
        string $gridName,
        array $sourceConfiguration,
        RawGridSourceDataAccessor $gridSourceDataAccessor,
        RepositorySourceFactory $repositorySourceFactory,
        GetterMethodsExtractor $getterMethodsExtractor,
        ExtensionAttributeTypeExtractor $extensionAttributeTypeExtractor,
        CustomAttributesExtractor $customAttributesExtractor,
        ColumnDefinitionInterfaceFactory $columnDefinitionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DataTypeGuesserInterface $dataTypeGuesser,
        ManagerInterface $eventManager
    ) {
        $this->gridName                        = $gridName;
        $this->sourceConfiguration             = $sourceConfiguration;
        $this->gridSourceDataAccessor          = $gridSourceDataAccessor;
        $this->repositorySourceFactory         = $repositorySourceFactory;
        $this->getterMethodsExtractor          = $getterMethodsExtractor;
        $this->extensionAttributeTypeExtractor = $extensionAttributeTypeExtractor;
        $this->customAttributesExtractor       = $customAttributesExtractor;
        $this->columnDefinitionFactory         = $columnDefinitionFactory;
        $this->searchCriteriaBuilder           = $searchCriteriaBuilder;
        $this->dataTypeGuesser                 = $dataTypeGuesser;
        $this->eventManager                    = $eventManager;
    }

    private function getGetMethodKeys(): array
    {
        if (!isset($this->getMethodKeys)) {
            $this->getMethodKeys = $this->getterMethodsExtractor->fromTypeAsFieldNames($this->getRecordType());
        }
        return $this->getMethodKeys;
    }

    private function getExtensionAttributeKeys(): array
    {
        if (!isset($this->extensionAttributeKeys)) {
            $type                         = $this->getRecordType();
            $extensionAttributesType      = $this->extensionAttributeTypeExtractor->forType($type);
            $this->extensionAttributeKeys = $extensionAttributesType
                ? $this->getterMethodsExtractor->fromTypeAsFieldNames($extensionAttributesType)
                : [];
        }
        return $this->extensionAttributeKeys;
    }

    private function getCustomAttributeKeys(): array
    {
        if (!isset($this->customAttributeKeys)) {
            $this->customAttributeKeys = $this->customAttributesExtractor->attributesForTypeAsFieldNames($this->getRecordType());
        }
        return $this->customAttributeKeys;
    }

    private function getSourceRepoConfig(): string
    {
        return $this->sourceConfiguration['repositoryListMethod'] ?? '';
    }

    public function getColumnKeys(): array
    {
        return unique(merge(
            $this->getCustomAttributeKeys(),
            $this->getExtensionAttributeKeys(),
            $this->getGetMethodKeys()
        ));
    }

    public function getColumnDefinition(string $key): ColumnDefinitionInterface
    {
        if (!isset($this->memoizedColumnDefinitions[$key])) {
            $this->memoizedColumnDefinitions[$key] = $this->buildColumnDefinition($key);
        }
        return $this->memoizedColumnDefinitions[$key];
    }

    private function buildColumnDefinition(string $key): ColumnDefinitionInterface
    {
        $recordType = $this->getRecordType();
        $columnType = $this->getColumnType($key, $recordType);
        $label      = $this->isCustomAttribute($key)
            ? $this->getCustomAttributeLabelByColumnKey($key, $recordType)
            : null;

        $options = $this->isCustomAttribute($key)
            ? $this->getCustomAttributeOptions($key)
            : null;

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

    private function getRecordType(): string
    {
        return $this->repositorySourceFactory->getRepositoryEntityType($this->getSourceRepoConfig());
    }

    private function getColumnType(string $key, string $recordType): string
    {
        if ($this->isSystemAttribute($key)) {
            $columnType = $this->getSourceMethodReturnTypeByColumnKey($key, $recordType);
        } elseif ($this->isExtensionAttribute($key)) {
            $columnType = $this->getExtensionAttributeTypeByColumnKey($key, $recordType);
        } elseif ($this->isCustomAttribute($key)) {
            $columnType = $this->getCustomAttributeTypeByColumnKey($key, $recordType);
        } else {
            $columnType = 'unknown';
        }
        return $this->dataTypeGuesser->typeToTypeCode($columnType);
    }

    private function getExtensionAttributeTypeByColumnKey(string $key, string $type): string
    {
        return $this->extensionAttributeTypeExtractor->getExtensionAttributeType($type, $key);
    }

    private function getSourceMethodReturnTypeByColumnKey(string $key, string $type): string
    {
        return $this->getterMethodsExtractor->getFieldType($type, $key);
    }

    private function getCustomAttributeTypeByColumnKey(string $key, string $type): ?string
    {
        return $this->customAttributesExtractor->getAttributeBackendType($type, $key);
    }

    private function getCustomAttributeLabelByColumnKey(string $key, string $type): ?string
    {
        return $this->customAttributesExtractor->getAttributeLabel($type, $key);
    }

    private function mapIdFilterToEntityIdField(SearchCriteriaInterface $searchCriteria): SearchCriteriaInterface
    {
        // preprocess $searchCriteria to map ìd to entity_id when applicable
        $idFieldName = $this->customAttributesExtractor->getIdFieldName($this->getRecordType());
        if ($idFieldName && $idFieldName !== 'id') {
            foreach ($searchCriteria->getFilterGroups() as $group) {
                foreach ($group->getFilters() as $filter) {
                    if ($filter->getField() === 'id') {
                        $filter->setField($idFieldName);
                    }
                }
            }

            if ($searchCriteria->getSortOrders()) {
                foreach ($searchCriteria->getSortOrders() as $sortOrder) {
                    if ($sortOrder->getField() == 'id') {
                        $sortOrder->setField($idFieldName);
                    }
                }
            }
        }
        return $searchCriteria;
    }

    private function preprocessSearchCriteria(SearchCriteriaInterface $searchCriteria): SearchCriteriaInterface
    {
        $searchCriteria = $this->mapIdFilterToEntityIdField($searchCriteria);
        return $this->dispatchGridRepositorySourcePrefetchEvent($searchCriteria);
    }

    public function fetchData(SearchCriteriaInterface $searchCriteria): RawGridSourceContainer
    {
        $preprocessedSearchCriteria = $this->preprocessSearchCriteria($searchCriteria);

        $repositoryGetList = $this->repositorySourceFactory->create($this->getSourceRepoConfig());
        $result            = $repositoryGetList($preprocessedSearchCriteria);

        return RawGridSourceContainer::forData($result);
    }

    public function extractRecords(RawGridSourceContainer $rawGridData): array
    {
        return $this->unboxRawGridSourceContainer($rawGridData)->getItems();
    }

    public function extractValue($record, string $key)
    {
        if ($this->isSystemAttribute($key)) {
            $value = $this->extractValueByMethod($record, $key);
        } elseif ($this->isExtensionAttribute($key)) {
            $value = $this->extractExtensionAttributeValue($record, $key);
        } elseif ($this->isCustomAttribute($key)) {
            $value = $this->extractCustomAttributeValue($record, $key);
        } else {
            $value = null;
        }
        return $value;
    }

    private function extractValueByMethod($record, string $key)
    {
        return $this->getterMethodsExtractor->getValue($record, $key);
    }

    private function extractExtensionAttributeValue($record, string $key)
    {
        $recordType = $this->getRecordType();

        return $this->extensionAttributeTypeExtractor->getValue($recordType, $record, $key);
    }

    private function extractCustomAttributeValue($record, string $key)
    {
        $value = $this->customAttributesExtractor->getValue($record, $key);
        return $this->getColumnDefinition($key)->getType() === 'array' && is_string($value)
            ? explode(',', $value)
            : $value;
    }

    private function getCustomAttributeOptions(string $key): ?array
    {
        $recordType = $this->getRecordType();
        $options    = $this->customAttributesExtractor->getAttributeOptions($recordType, $key);
        return filter($options, function (array $option) {
            return $option['value'] !== '';
        });
    }

    /**
     * Note: no return type specified on purpose, because sometimes the core code doesn't adhere to conventions.
     *
     * @param RawGridSourceContainer $rawGridData
     * @return SearchResults
     */
    private function unboxRawGridSourceContainer(RawGridSourceContainer $rawGridData)
    {
        return $this->gridSourceDataAccessor->unbox($rawGridData);
    }

    public function extractTotalRowCount(RawGridSourceContainer $rawGridData): int
    {
        return $this->unboxRawGridSourceContainer($rawGridData)->getTotalCount();
    }

    private function dispatchGridRepositorySourcePrefetchEvent(
        SearchCriteriaInterface $searchCriteria
    ): SearchCriteriaInterface {
        $eventName = 'hyva_grid_repository_source_prefetch_' . $this->getGridNameEventSuffix();
        $container = new SearchCriteriaEventContainer($searchCriteria);
        $eventArgs = ['search_criteria_container' => $container];
        $this->eventManager->dispatch($eventName, $eventArgs);

        return $container->getSearchCriteria();
    }

    private function getGridNameEventSuffix(): string
    {
        return strtolower(preg_replace('/[^[:alpha:]]+/', '_', $this->gridName));
    }

    private function isSystemAttribute(string $key): bool
    {
        return in_array($key, $this->getGetMethodKeys(), true);
    }

    private function isExtensionAttribute(string $key): bool
    {
        return in_array($key, $this->getExtensionAttributeKeys(), true);
    }

    private function isCustomAttribute(string $key): bool
    {
        return in_array($key, $this->getCustomAttributeKeys(), true);
    }

    private function isNonSortableColumn(string $key, string $recordType, string $columnType): bool
    {
        if ($this->isExtensionAttribute($key)) {
            // Most extension attributes are persisted in separate tables loaded in a separate query.
            return true;
        }
        $isProductRecords = ltrim($recordType, '\\') === ProductInterface::class;

        if ($isProductRecords && $columnType === ProductGalleryDataType::TYPE_MAGENTO_PRODUCT_GALLERY) {
            // The media gallery attributes are loaded in a separate query when Product::getMediaGalleryImages is called
            // The eav_attribute.backend_type is falsely set to static, so sorting attempts blow up.
            return true;
        }

        if ($isProductRecords && $key === 'category_ids') {
            // Category IDs are loaded in a separate query when Product::getCategoryIds is called.
            // The eav_attribute.backend_type is falsely set to static, so sorting attempts blow up.
            return true;
        }

        return false;
    }
}
