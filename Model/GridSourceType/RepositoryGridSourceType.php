<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use Hyva\Admin\Model\GridSourcePrefetchEventDispatcher;
use Hyva\Admin\Model\DataType\ProductGalleryDataType;
use Hyva\Admin\Model\GridSourceType\Internal\RawGridSourceDataAccessor;
use Hyva\Admin\Model\GridSourceType\RepositorySourceType\RepositorySourceFactory;
use Hyva\Admin\Model\RawGridSourceContainer;
use Hyva\Admin\Model\TypeReflection;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResults;

use function array_filter as filter;

class RepositoryGridSourceType implements GridSourceTypeInterface
{
    /**
     * @var string
     */
    private $gridName;

    /**
     * @var mixed[]
     */
    private $sourceConfiguration;

    /**
     * @var \Hyva\Admin\Model\GridSourceType\Internal\RawGridSourceDataAccessor
     */
    private $gridSourceDataAccessor;

    /**
     * @var \Hyva\Admin\Model\GridSourceType\RepositorySourceType\RepositorySourceFactory
     */
    private $repositorySourceFactory;

    /**
     * @var \Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory
     */
    private $columnDefinitionFactory;

    /**
     * @var \Hyva\Admin\Model\TypeReflection
     */
    private $typeReflection;

    /**
     * @var \Hyva\Admin\Model\GridSourcePrefetchEventDispatcher
     */
    private $gridSourcePrefetchEventDispatcher;

    /**
     * @var mixed[]
     */
    private $memoizedColumnDefinitions = [];

    /**
     * @var string
     */
    private $memoizedRecordType;

    public function __construct(
        string $gridName,
        array $sourceConfiguration,
        RawGridSourceDataAccessor $gridSourceDataAccessor,
        RepositorySourceFactory $repositorySourceFactory,
        ColumnDefinitionInterfaceFactory $columnDefinitionFactory,
        GridSourcePrefetchEventDispatcher $gridSourcePrefetchEventDispatcher,
        TypeReflection $typeReflection
    ) {
        $this->gridName                          = $gridName;
        $this->sourceConfiguration               = $sourceConfiguration;
        $this->gridSourceDataAccessor            = $gridSourceDataAccessor;
        $this->repositorySourceFactory           = $repositorySourceFactory;
        $this->columnDefinitionFactory           = $columnDefinitionFactory;
        $this->typeReflection                    = $typeReflection;
        $this->gridSourcePrefetchEventDispatcher = $gridSourcePrefetchEventDispatcher;
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
        if (!isset($this->memoizedRecordType)) {
            $config                   = $this->getSourceRepoConfig();
            $this->memoizedRecordType = $this->repositorySourceFactory->getRepositoryEntityType($config);
        }
        return $this->memoizedRecordType;
    }

    public function fetchData(SearchCriteriaInterface $searchCriteria): RawGridSourceContainer
    {
        $preprocessedSearchCriteria = $this->gridSourcePrefetchEventDispatcher->dispatch(
            $this->gridName,
            $this->getRecordType(),
            $searchCriteria
        );

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
