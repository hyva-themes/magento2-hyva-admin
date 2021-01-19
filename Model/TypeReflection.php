<?php declare(strict_types=1);

namespace Hyva\Admin\Model;

use Hyva\Admin\Api\DataTypeGuesserInterface;
use Hyva\Admin\Model\TypeReflection\MagentoOrmReflection;

use function array_filter as filter;
use function array_merge as merge;
use function array_unique as unique;

class TypeReflection
{
    private TypeReflection\CustomAttributesExtractor $customAttributesExtractor;

    private TypeReflection\ExtensionAttributeTypeExtractor $extensionAttributeTypeExtractor;

    private TypeReflection\GetterMethodsExtractor $getterMethodsExtractor;

    private DataTypeGuesserInterface $dataTypeGuesser;

    private array $customAttributeKeys = [];

    private array $extensionAttributeKeys = [];

    private array $getMethodKeys = [];

    private MagentoOrmReflection $magentoOrmReflection;

    private array $memoizedColumnTypes = [];

    public function __construct(
        TypeReflection\CustomAttributesExtractor $customAttributesExtractor,
        TypeReflection\ExtensionAttributeTypeExtractor $extensionAttributeTypeExtractor,
        TypeReflection\GetterMethodsExtractor $getterMethodsExtractor,
        DataTypeGuesserInterface $dataTypeGuesser,
        MagentoOrmReflection $magentoOrmReflection
    ) {
        $this->customAttributesExtractor       = $customAttributesExtractor;
        $this->extensionAttributeTypeExtractor = $extensionAttributeTypeExtractor;
        $this->getterMethodsExtractor          = $getterMethodsExtractor;
        $this->dataTypeGuesser                 = $dataTypeGuesser;
        $this->magentoOrmReflection            = $magentoOrmReflection;
    }

    public function getFieldNames(string $type): array
    {
        return unique(merge(
            $this->getCustomAttributeKeys($type),
            $this->getExtensionAttributeKeys($type),
            $this->getGetMethodKeys($type)
        ));
    }

    private function getCustomAttributeKeys(string $type): array
    {
        if (!isset($this->customAttributeKeys[$type])) {
            $this->customAttributeKeys[$type] = $this->customAttributesExtractor->attributesForTypeAsFieldNames($type);
        }
        return $this->customAttributeKeys[$type];
    }

    private function getExtensionAttributeKeys(string $type): array
    {
        if (!isset($this->extensionAttributeKeys[$type])) {
            $extensionAttributesType             = $this->extensionAttributeTypeExtractor->forType($type);
            $this->extensionAttributeKeys[$type] = $extensionAttributesType
                ? $this->getterMethodsExtractor->fromTypeAsFieldNames($extensionAttributesType)
                : [];
        }
        return $this->extensionAttributeKeys[$type];
    }

    private function getGetMethodKeys(string $type): array
    {
        if (!isset($this->getMethodKeys[$type])) {
            $this->getMethodKeys[$type] = $this->getterMethodsExtractor->fromTypeAsFieldNames($type);
        }
        return $this->getMethodKeys[$type];
    }

    public function isSystemAttribute(string $type, string $key): bool
    {
        return in_array($key, $this->getGetMethodKeys($type), true);
    }

    public function isExtensionAttribute(string $type, string $key): bool
    {
        return in_array($key, $this->getExtensionAttributeKeys($type), true);
    }

    public function isCustomAttribute(string $type, string $key): bool
    {
        return in_array($key, $this->getCustomAttributeKeys($type), true);
    }

    public function getColumnType(string $phpType, string $key): string
    {
        if (! isset($this->memoizedColumnTypes[$phpType][$key])) {
            $this->memoizedColumnTypes[$phpType][$key] = $this->determineColumnType($phpType, $key);
        }
        return $this->memoizedColumnTypes[$phpType][$key];
    }

    private function determineColumnType(string $phpType, string $key): string
    {
        if ($this->isSystemAttribute($phpType, $key)) {
            $columnType = $this->getterMethodsExtractor->getFieldType($phpType, $key);
        } elseif ($this->isExtensionAttribute($phpType, $key)) {
            $columnType = $this->extensionAttributeTypeExtractor->getExtensionAttributeType($phpType, $key);
        } elseif ($this->isCustomAttribute($phpType, $key)) {
            $columnType = $this->customAttributesExtractor->getAttributeBackendType($phpType, $key);
        } else {
            $columnType = 'unknown';
        }
        return $this->dataTypeGuesser->typeToTypeCode($columnType);
    }

    public function extractValue(string $type, string $key, $object)
    {
        if ($this->isSystemAttribute($type, $key)) {
            $value = $this->getterMethodsExtractor->getValue($object, $key);
        } elseif ($this->isExtensionAttribute($type, $key)) {
            $value = $this->extensionAttributeTypeExtractor->getValue($type, $key, $object);
        } elseif ($this->isCustomAttribute($type, $key)) {
            $value = $this->extractCustomAttributeValue($type, $key, $object);
        } else {
            $value = null;
        }
        return $value;
    }

    private function extractCustomAttributeValue(string $type, string $key, $object)
    {
        $value = $this->customAttributesExtractor->getValue($object, $key);
        return $this->getColumnType($type, $key) === 'array' && is_string($value)
            ? explode(',', $value)
            : $value;
    }

    public function extractLabel(string $type, string $key): ?string
    {
        return $this->isCustomAttribute($type, $key)
            ? $this->customAttributesExtractor->getAttributeLabel($type, $key)
            : null;
    }

    public function extractOptions(string $type, string $key): ?array
    {
        return $this->isCustomAttribute($type, $key)
            ? $this->getCustomAttributeOptions($type, $key)
            : null;
    }

    private function getCustomAttributeOptions(string $type, string $key): ?array
    {
        $options = $this->customAttributesExtractor->getAttributeOptions($type, $key);
        return filter($options, function (array $option) {
            return $option['value'] !== '';
        });
    }

    public function getIdFieldName(string $type): ?string
    {
        return $this->magentoOrmReflection->getIdFieldNameForType($type);
    }

    public function isUserDefined(string $phpType, string $key): string
    {
        return $this->customAttributesExtractor->isAttributeUserDefined($phpType, $key);
    }
}
