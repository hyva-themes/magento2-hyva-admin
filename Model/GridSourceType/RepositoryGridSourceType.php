<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType;

use Hyva\Admin\Model\GridSourceType\Internal\RawGridSourceDataAccessor;
use Hyva\Admin\Model\GridSourceType\RepositorySourceType\RepositorySourceFactory;
use Hyva\Admin\Model\RawGridSourceContainer;
use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Magento\Framework\Api\ExtensionAttributesInterface;
use Magento\Framework\Reflection\FieldNamer;
use Magento\Framework\Reflection\MethodsMap;

use function array_keys as keys;
use function array_filter as filter;
use function array_map as map;
use function array_merge as merge;
use function array_unique as unique;

class RepositoryGridSourceType implements GridSourceTypeInterface
{

    private string $gridName;

    private array $sourceConfiguration;

    private RawGridSourceDataAccessor $gridSourceDataAccessor;

    private RepositorySourceFactory $repositorySourceFactory;

    private MethodsMap $methodsMap;

    private FieldNamer $fieldNamer;

    public function __construct(
        string $gridName,
        array $sourceConfiguration,
        RawGridSourceDataAccessor $gridSourceDataAccessor,
        RepositorySourceFactory $repositorySourceFactory,
        MethodsMap $methodsMap,
        FieldNamer $fieldNamer
    ) {
        $this->gridName                = $gridName;
        $this->sourceConfiguration     = $sourceConfiguration;
        $this->gridSourceDataAccessor  = $gridSourceDataAccessor;
        $this->repositorySourceFactory = $repositorySourceFactory;
        $this->methodsMap              = $methodsMap;
        $this->fieldNamer              = $fieldNamer;
    }

    private function getSourceRepoConfig(): string
    {
        return $this->sourceConfiguration['repositoryListMethod'] ?? '';
    }

    public function getColumnKeys(): array
    {
        $sourceConfig           = $this->getSourceRepoConfig();
        $recordType             = $this->repositorySourceFactory->getRepositoryEntityType($sourceConfig);
        $getMethodKeys          = $this->getKeysFromGetters($recordType);
        // todo next step: add in extension attributes and custom attributes
        $extensionAttributeKeys = $this->getExtensionAttributeKeys($recordType);
        $customAttributeKeys    = $this->getCustomAttributeKeys($recordType);


        return unique(merge($getMethodKeys, $extensionAttributeKeys, $customAttributeKeys));
    }

    private function getKeysFromGetters(string $recordType): array
    {
        $methods          = keys($this->methodsMap->getMethodsMap($recordType));
        $potentialColumns = filter($methods, function (string $method) use ($recordType): bool {
            return (bool) $this->methodsMap->isMethodValidForDataField($recordType, $method);
        });
        $getters          = filter($potentialColumns, function (string $method) use ($recordType): bool {
            $returnType = $this->methodsMap->getMethodReturnType($recordType, $method);
            return $this->isFieldGetter($method, $returnType);
        });
        return map([$this->fieldNamer, 'getFieldNameForMethodName'], $getters);
    }

    private function getExtensionAttributeKeys(string $recordType): array
    {
        return [];
    }

    private function getCustomAttributeKeys(string $recordType): array
    {
        return [];
    }

    private function isFieldGetter(string $method, string $returnType): bool
    {
        return substr($method, 0, 3) === 'get' && $method !== 'getCustomAttributes'
            && $returnType
            && !is_subclass_of($returnType, ExtensionAttributesInterface::class);
    }

    public function extractValue($record, string $key)
    {

    }

    public function getColumnDefinition(string $key): ColumnDefinitionInterface
    {

    }

    public function fetchData(): RawGridSourceContainer
    {

    }

    public function extractRecords(RawGridSourceContainer $rawGridData): array
    {

    }
}
