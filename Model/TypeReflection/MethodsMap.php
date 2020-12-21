<?php declare(strict_types=1);

namespace Hyva\Admin\Model\TypeReflection;

use function array_column as pick;
use function array_combine as zip;
use function array_filter as filter;
use function array_keys as keys;
use function array_merge as merge;
use function array_reduce as reduce;
use function array_slice as slice;
use function array_values as values;

use Laminas\Code\Reflection\ClassReflection;
use Laminas\Code\Reflection\DocBlock\Tag\MethodTag;
use Laminas\Code\Reflection\DocBlock\Tag\ReturnTag;
use Laminas\Code\Reflection\MethodReflection;
use Magento\Framework\Reflection\FieldNamer;

/**
 * This class is different from \Magento\Framework\Reflection\MethodsMap in several ways
 *
 * - Doesn't throw exceptions for missing return type phpdoc annotations
 * - Reads return types from method signatures
 * - attempts to return annotated type[] array return types when the signature return type is array
 */
class MethodsMap
{
    private FieldNamer $fieldNamer;

    /**
     * @var array[]
     */
    private $memoizedMethodMaps = [];

    private NamespaceMapper $namespaceMapper;

    public function __construct(FieldNamer $fieldNamer, NamespaceMapper $namespaceMapper)
    {
        $this->fieldNamer      = $fieldNamer;
        $this->namespaceMapper = $namespaceMapper;
    }

    /**
     * Return map of method name => return type for given type.
     *
     * The map includes annotated methods from the types PHPDoc block.
     * If a methods return type is unspecified, the map value is null.
     */
    public function getMethodsMap(string $type): array
    {
        $this->initMethodMapForType($type);
        return zip(keys($this->memoizedMethodMaps[$type]), pick($this->memoizedMethodMaps[$type], 'return'));
    }

    private function initMethodMapForType(string $type): void
    {
        if (!isset($this->memoizedMethodMaps[$type])) {
            $this->memoizedMethodMaps[$type] = $this->buildMethodMap($type);
        }
    }

    public function getMethodReturnType(string $type, string $methodName): ?string
    {
        return $this->getMethodsMap($type)[$methodName] ?? null;
    }

    public function isMethodValidForDataField(string $type, string $methodName): bool
    {
        $this->initMethodMapForType($type);
        $methodInfo = $this->memoizedMethodMaps[$type][$methodName] ?? false;
        return $methodInfo &&
            $methodInfo['return'] !== 'void' &&
            $methodInfo['parameterCount'] === 0 &&
            $this->fieldNamer->getFieldNameForMethodName($methodName);
    }

    private function buildMethodMap(string $type): array
    {
        $class             = new ClassReflection($type);
        $realMethods       = $this->getRealMethods($class);
        $annotationMethods = $this->getAnnotationMethods($class);

        return merge([], $realMethods, $annotationMethods);
    }

    private function getAnnotationMethods(ClassReflection $class): array
    {
        $methodAnnotations = $class->getDocBlock() ? $class->getDocBlock()->getTags('method') : [];
        // Methods annotations with params are returned as instances with a null method name, which we can ignore
        // because in the end we are only interested in getters without parameters.
        $methodsWithName = filter($methodAnnotations, fn(MethodTag $method): bool => (bool) $method->getMethodName());

        return reduce($methodsWithName, function (array $map, MethodTag $tag) use ($class): array {
            $type = $this->reduceToSingleType($tag->getTypes());
            $type = $this->qualifyNamespace($type, $class);

            return merge($map, [rtrim($tag->getMethodName(), '()') => ['return' => $type, 'parameterCount' => 0]]);
        }, []);
    }

    private function reduceToSingleType(array $returnTypes, ?string $default = 'mixed'): ?string
    {
        return count($returnTypes) === 1 && $returnTypes[0] === 'null'
            ? 'void'
            : values(filter($returnTypes, fn(string $type): bool => $type !== 'null'))[0] ?? $default;
    }

    private function getRealMethods(ClassReflection $class): array
    {
        $publicMethods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

        return reduce($publicMethods, function (array $map, MethodReflection $method) use ($class): array {
            $returnType = $this->determineReturnType($method);
            $methodInfo = ['return' => $returnType ?? 'mixed', 'parameterCount' => $method->getNumberOfParameters()];
            return merge($map, [$method->getName() => $methodInfo]);
        }, []);
    }

    private function determineReturnType(MethodReflection $method): ?string
    {
        return $this->getMethodSignatureReturnType($method) ?? $this->getAnnotatedReturnType($method);
    }

    private function getMethodSignatureReturnType(MethodReflection $method): ?string
    {
        $type = $method->getReturnType();
        if ($type) {
            return $type->getName() === 'array' ? $this->getAnnotatedArrayType($method) : $type->getName();
        }
        return null;
    }

    private function getAnnotatedArrayType(MethodReflection $method): string
    {
        $annotatedReturnType = $this->getAnnotatedReturnType($method);

        return $annotatedReturnType && substr($annotatedReturnType, -2) === '[]'
            ? $annotatedReturnType
            : 'mixed[]';
    }

    private function getAnnotatedReturnType(?MethodReflection $method): ?string
    {
        if (!$method) {
            return null;
        }
        $returnType = $this->readAnnotatedReturnTypeFromMethod($method)
            ?? $this->getAnnotatedReturnType($this->getParentReflectionMethod($method));
        return $returnType
            ? $this->qualifyNamespace($returnType, $method->getDeclaringClass())
            : null;
    }

    private function getParentReflectionMethod(MethodReflection $method): ?MethodReflection
    {
        $parent = $method->getDeclaringClass()->getParentClass();
        return $parent && $parent->hasMethod($method->getName())
            ? $parent->getMethod($method->getName())
            : null;
    }

    private function readAnnotatedReturnTypeFromMethod(MethodReflection $method): ?string
    {
        return ($tag = $this->getLastReturnTypeAnnotation($method))
            ? $this->reduceToSingleType($tag->getTypes())
            : null;
    }

    private function getLastReturnTypeAnnotation(MethodReflection $method): ?ReturnTag
    {
        return ($method->getDocBlock() && ($tags = $method->getDocBlock()->getTags('return')))
            ? slice(values($tags), -1)[0]
            : null;
    }

    private function isQualifiedTypeName(?string $type): bool
    {
        $nonArrayType = rtrim((string) $type, '[]');
        $pseudoTypes  = ['string', 'int', 'resource', 'null', 'void', 'decimal', 'float', 'bool', 'array', 'mixed'];
        return in_array($nonArrayType, $pseudoTypes) || class_exists($nonArrayType) || interface_exists($nonArrayType);
    }

    private function qualifyNamespace(?string $type, ClassReflection $class): string
    {
        return $this->isQualifiedTypeName($type)
            ? $type
            : $this->namespaceMapper->forFile($class->getFileName())->qualify($type);
    }
}
