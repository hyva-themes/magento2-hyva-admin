<?php declare(strict_types=1);

namespace Hyva\Admin\Model\GridSourceType\RepositorySourceType;

use Magento\Framework\Api\ExtensionAttributesInterface;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\Reflection\FieldNamer;
use Magento\Framework\Reflection\MethodsMap;

use function array_keys as keys;
use function array_filter as filter;
use function array_map as map;
use function array_values as values;

class GetterMethodsExtractor
{
    private MethodsMap $methodsMap;

    private FieldNamer $fieldNamer;

    public function __construct(MethodsMap $methodsMap, FieldNamer $fieldNamer)
    {
        $this->methodsMap = $methodsMap;
        $this->fieldNamer = $fieldNamer;
    }

    public function fromType(string $type): array
    {
        $methods          = keys($this->methodsMap->getMethodsMap($type));
        $potentialGetters = filter($methods, function (string $method) use ($type): bool {
            return (bool) $this->methodsMap->isMethodValidForDataField($type, $method);
        });
        return values(filter($potentialGetters, function (string $method) use ($type): bool {
            $returnType = $this->methodsMap->getMethodReturnType($type, $method);
            return $this->isFieldGetter($method, $returnType);
        }));
    }

    private function isFieldGetter(string $method, string $returnType): bool
    {
        $hasGetterPrefix = in_array(substr($method, 0, 3), ['get', 'has'], true) || substr($method, 0, 2) === 'is';
        return $hasGetterPrefix
            && $method !== 'getCustomAttributes'
            && $returnType !== 'void'
            && !$this->isExtensionAttributesType($returnType);
    }

    private function isExtensionAttributesType(string $returnType): bool
    {
        return is_subclass_of($returnType, ExtensionAttributesInterface::class);
    }

    public function fromTypeAsFieldNames(string $type): array
    {
        return map([$this->fieldNamer, 'getFieldNameForMethodName'], $this->fromType($type));
    }

    private function getMethodReturnType(string $type, string $method): ?string
    {
        return method_exists($type, $method)
            ? $this->methodsMap->getMethodReturnType($type, $method)
            : null;
    }

    /**
     * @param string|object $type
     * @param string $key
     * @param string[] $prefixes
     * @return string
     */
    private function getMethodNameFromKey($type, string $key, array $prefixes = ['get', 'has', 'is']): string
    {
        $prefix = array_shift($prefixes);
        if (! $prefix) {
            return '';
        }
        $method = $prefix . SimpleDataObjectConverter::snakeCaseToUpperCamelCase($key);
        return method_exists($type, $method)
            ? $method
            : $this->getMethodNameFromKey($type, $key, $prefixes);
    }

    public function getFieldType(string $type, string $key): string
    {
        $method = $this->getMethodNameFromKey($type, $key);
        $returnType = $this->getMethodReturnType($type, $method) ?? 'unknown';

        return substr($returnType, -2) === '[]'
            ? 'array'
            : $returnType;
    }

    public function getValue($object, string $key)
    {
        $method = $this->getMethodNameFromKey($object, $key);
        return $object->{$method}();
    }
}
