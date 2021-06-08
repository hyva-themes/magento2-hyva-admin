<?php declare(strict_types=1);

namespace Hyva\Admin\Model\TypeReflection;

use function array_diff as diff;
use function array_filter as filter;
use function array_keys as keys;
use function array_map as map;
use function array_reduce as reduce;
use function array_values as values;

use Magento\Framework\Api\ExtensionAttributesInterface;
use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Reflection\FieldNamer;

class GetterMethodsExtractor
{
    /**
     * @var MethodsMap
     */
    private $methodsMap;

    /**
     * @var FieldNamer
     */
    private $fieldNamer;

    public function __construct(MethodsMap $methodsMap, FieldNamer $fieldNamer)
    {
        $this->methodsMap = $methodsMap;
        $this->fieldNamer = $fieldNamer;
    }

    private function removeGenericParentClassMethods(string $type, array $methods): array
    {
        return reduce(
            [AbstractModel::class, DataObject::class],
            function (array $methods, string $parent) use ($type): array {
                return is_subclass_of($type, $parent)
                    ? diff($methods, $this->getGenericParentClassMethods($parent))
                    : $methods;
            },
            $methods
        );
    }

    private function getGenericParentClassMethods(string $class): array
    {
        $methods = keys($this->methodsMap->getMethodsReturnTypeMap($class));
        // exclude getId since it needs to be inherited as a field on child classes
        return filter($methods, function (string $method): bool {
            return $method !== 'getId';
        });
    }

    private function isMethodValidGetter(string $type, string $method): bool
    {
        return (bool) $this->methodsMap->isMethodValidGetter($type, $method);
    }

    private function fromType(string $type): array
    {
        return class_exists($type) || interface_exists($type)
            ? $this->buildMethodList($type)
            : [];
    }

    private function buildMethodList(string $type): array
    {
        $allMethods       = keys($this->methodsMap->getMethodsReturnTypeMap($type));
        $methods          = $this->removeGenericParentClassMethods($type, $allMethods);
        $potentialGetters = filter($methods, function (string $method) use ($type) {
            return $this->isMethodValidGetter($type, $method);
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
        return $this->methodsMap->getMethodReturnType($type, $method);
    }

    /**
     * @param string|object $type
     * @param string $key
     * @param string[] $prefixes
     * @return string
     */
    private function getMethodNameFromKey($type, string $key, array $prefixes = ['is', 'has', 'get']): string
    {
        $prefix = array_shift($prefixes);
        if (!$prefix) {
            return '';
        }
        $method = $prefix . SimpleDataObjectConverter::snakeCaseToUpperCamelCase($key);
        return method_exists($type, $method) || ($prefix === 'get' && method_exists($type, '__call'))
            ? $method
            : $this->getMethodNameFromKey($type, $key, $prefixes);
    }

    public function getFieldType(string $type, string $key): string
    {
        $method     = $this->getMethodNameFromKey($type, $key);
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
