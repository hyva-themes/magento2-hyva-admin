<?php declare(strict_types=1);

namespace Hyva\Admin\Model\TypeReflection;

use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Api\ExtensionAttributesInterface;
use Magento\Framework\Api\SimpleDataObjectConverter;

use function array_filter as filter;
use function array_keys as keys;
use function array_values as values;

class ExtensionAttributeTypeExtractor
{
    /**
     * @var MethodsMap
     */
    private $methodsMap;

    public function __construct(MethodsMap $methodsMap)
    {
        $this->methodsMap = $methodsMap;
    }

    public function forType(string $type): ?string
    {
        $extensionAttributesGetter = is_subclass_of($type, ExtensibleDataInterface::class)
            ? $this->getGetterExtensionAttributesGetterMethod($type)
            : null;
        return $extensionAttributesGetter
            ? $this->getMethodReturnType($type, $extensionAttributesGetter)
            : null;
    }

    private function getGetterExtensionAttributesGetterMethod(string $type): ?string
    {
        $methods = keys($this->methodsMap->getMethodsReturnTypeMap($type));
        return values(filter($methods, function (string $method) use ($type): bool {
                $returnType = $this->getMethodReturnType($type, $method);
                return $this->isExtensionAttributesType($returnType);
            }))[0] ?? null;
    }

    private function isExtensionAttributesType(string $returnType): bool
    {
        return is_subclass_of($returnType, ExtensionAttributesInterface::class);
    }

    private function getMethodReturnType(string $type, string $method): ?string
    {
        return $this->methodsMap->getMethodReturnType($type, $method);

    }

    public function getExtensionAttributeType(string $type, string $key): string
    {
        $extensionAttributesType = $this->forType($type);
        $returnType              = $this->getMethodReturnType($extensionAttributesType,
            $this->getMethodNameForKey($key));
        return substr($returnType, -2) === '[]'
            ? 'array'
            : $returnType;
    }

    public function getValue(string $type, string $key, $object)
    {
        $extensionAttributesGetter = $this->getGetterExtensionAttributesGetterMethod($type);
        $extensionAttributes       = $object->{$extensionAttributesGetter}();

        $method = $this->getMethodNameForKey($key);
        return $extensionAttributes->{$method}();
    }

    private function getMethodNameForKey(string $key): string
    {
        return 'get' . SimpleDataObjectConverter::snakeCaseToUpperCamelCase($key);
    }
}
