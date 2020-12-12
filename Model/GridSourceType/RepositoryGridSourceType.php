<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use Hyva\Admin\Api\DataTypeGuesserInterface;
use Hyva\Admin\Model\DataType\ProductGalleryDataType;
use Hyva\Admin\Model\GridSourceType\Internal\RawGridSourceDataAccessor;
use Hyva\Admin\Model\GridSourceType\RepositorySourceType\RepositorySourceFactory;
use Hyva\Admin\Model\GridSourceType\RepositorySourceType\SearchCriteriaEventContainer;
use Hyva\Admin\Model\TypeReflection;
use Hyva\Admin\Model\RawGridSourceContainer;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;
use Magento\Framework\Event\ManagerInterface;

use function array_filter as filter;

class RepositoryGridSourceType implements GridSourceTypeInterface
{
    private string $gridName;

    private array $sourceConfiguration;

    private RawGridSourceDataAccessor $gridSourceDataAccessor;

    private RepositorySourceFactory $repositorySourceFactory;

    private ColumnDefinitionInterfaceFactory $columnDefinitionFactory;

    private SearchCriteriaBuilder $searchCriteriaBuilder;

    private DataTypeGuesserInterface $dataTypeGuesser;

    private ManagerInterface $eventManager;

    /**
     * @var ColumnDefinitionInterface[]
     */
    private $memoizedColumnDefinitions = [];

    private TypeReflection $typeReflection;

    public function __construct(
        string $gridName,
        array $sourceConfiguration,
        RawGridSourceDataAccessor $gridSourceDataAccessor,
        RepositorySourceFactory $repositorySourceFactory,
        ColumnDefinitionInterfaceFactory $columnDefinitionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        DataTypeGuesserInterface $dataTypeGuesser,
        ManagerInterface $eventManager,
        TypeReflection $typeReflection
    ) {
        $this->gridName                = $gridName;
        $this->sourceConfiguration     = $sourceConfiguration;
        $this->gridSourceDataAccessor  = $gridSourceDataAccessor;
        $this->repositorySourceFactory = $repositorySourceFactory;
        $this->columnDefinitionFactory = $columnDefinitionFactory;
        $this->searchCriteriaBuilder   = $searchCriteriaBuilder;
        $this->dataTypeGuesser         = $dataTypeGuesser;
        $this->eventManager            = $eventManager;
        $this->typeReflection          = $typeReflection;
    }

    private function getSourceRepoConfig(): string
    {
        return $this->sourceConfiguration['repositoryListMethod'] ?? '';
    }

    public function getColumnKeys(): array
    {
        return $this->typeReflection->getFieldNames($this->getRecordType());
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

    private function getRecordType(): string
    {
        return $this->repositorySourceFactory->getRepositoryEntityType($this->getSourceRepoConfig());
    }

    private function mapIdFilterToEntityIdField(SearchCriteriaInterface $searchCriteria): SearchCriteriaInterface
    {
        // preprocess $searchCriteria to map ìd to entity_id when applicable
        $idFieldName = $this->typeReflection->getIdFieldName($this->getRecordType());
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
        return $this->typeReflection->extractValue($this->getRecordType(), $key, $record);
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

    private function isExtensionAttribute(string $key): bool
    {
        return $this->typeReflection->isExtensionAttribute($this->getRecordType(), $key);
    }
}
