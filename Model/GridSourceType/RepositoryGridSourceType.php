<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use Hyva\Admin\Model\GridSourceType\RepositorySourceType\CustomAttributesExtractor;
use Hyva\Admin\Model\GridSourceType\RepositorySourceType\ExtensionAttributeTypeExtractor;
use Hyva\Admin\Model\GridSourceType\RepositorySourceType\GetterMethodsExtractor;
use Hyva\Admin\Model\GridSourceType\Internal\RawGridSourceDataAccessor;
use Hyva\Admin\Model\GridSourceType\RepositorySourceType\RepositorySourceFactory;
use Hyva\Admin\Model\RawGridSourceContainer;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterfaceFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResults;

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

    private ColumnDefinitionInterfaceFactory $columnDefinitionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    public function __construct(
        string $gridName,
        array $sourceConfiguration,
        RawGridSourceDataAccessor $gridSourceDataAccessor,
        RepositorySourceFactory $repositorySourceFactory,
        GetterMethodsExtractor $getterMethodsExtractor,
        ExtensionAttributeTypeExtractor $extensionAttributeTypeExtractor,
        CustomAttributesExtractor $customAttributesExtractor,
        ColumnDefinitionInterfaceFactory $columnDefinitionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder
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
        $recordType = $this->getRecordType();
        $columnType = $this->getColumnType($key, $recordType);
        $label      = in_array($key, $this->getCustomAttributeKeys(), true)
            ? $this->getCustomAttributeLabelByColumnKey($key, $recordType)
            : null;

        $options = in_array($key, $this->getCustomAttributeKeys(), true)
            ? $this->getCustomAttributeOptions($key)
            : null;

        $constructorArguments = filter(['key' => $key, 'type' => $columnType, 'label' => $label, 'options' => $options]);
        return $this->columnDefinitionFactory->create($constructorArguments);
    }

    private function getRecordType(): string
    {
        return $this->repositorySourceFactory->getRepositoryEntityType($this->getSourceRepoConfig());
    }

    private function getColumnType(string $key, string $recordType): string
    {
        if (in_array($key, $this->getGetMethodKeys(), true)) {
            $columnType = $this->getSourceMethodReturnTypeByColumnKey($key, $recordType);
        } elseif (in_array($key, $this->getExtensionAttributeKeys(), true)) {
            $columnType = $this->getExtensionAttributeTypeByColumnKey($key, $recordType);
        } elseif (in_array($key, $this->getCustomAttributeKeys(), true)) {
            $columnType = $this->getCustomAttributeTypeByColumnKey($key, $recordType);
        } else {
            $columnType = 'unknown';
        }
        return $columnType;
    }

    private function getExtensionAttributeTypeByColumnKey(string $key, string $type): string
    {
        return $this->extensionAttributeTypeExtractor->getExtensionAttributeType($type, $key);
    }

    private function getSourceMethodReturnTypeByColumnKey(string $key, string $type): string
    {
        return $this->getterMethodsExtractor->getFieldType($type, $key);
    }

    private function getCustomAttributeTypeByColumnKey(string $key, string $type): string
    {
        return $this->customAttributesExtractor->getAttributeBackendType($type, $key);
    }

    private function getCustomAttributeLabelByColumnKey(string $key, string $type): string
    {
        return $this->customAttributesExtractor->getAttributeLabel($type, $key);
    }

    public function fetchData(): RawGridSourceContainer
    {
        $repositoryGetList = $this->repositorySourceFactory->create($this->getSourceRepoConfig());
        $result            = $repositoryGetList($this->searchCriteriaBuilder->create());

        return RawGridSourceContainer::forData($result);
    }

    public function extractRecords(RawGridSourceContainer $rawGridData): array
    {
        /** @var SearchResults $result */
        $result = $this->gridSourceDataAccessor->unbox($rawGridData);

        return $result->getItems();
    }

    public function extractValue($record, string $key)
    {
        if (in_array($key, $this->getGetMethodKeys(), true)) {
            $value = $this->extractValueByMethod($record, $key);
        } elseif (in_array($key, $this->getExtensionAttributeKeys(), true)) {
            $value = $this->extractExtensionAttributeValue($record, $key);
        } elseif (in_array($key, $this->getCustomAttributeKeys(), true)) {
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
        $recordType = $this->getRecordType();
        return $this->customAttributesExtractor->getValue($recordType, $record, $key);
    }

    private function getCustomAttributeOptions(string $key): ?array
    {
        $recordType = $this->getRecordType();
        $options = $this->customAttributesExtractor->getAttributeOptions($recordType, $key);
        return filter($options, function (array $option) {
            return $option['value'] !== '';
        });
    }

}
