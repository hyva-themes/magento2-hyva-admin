<?php declare(strict_types=1);

namespace Hyva\Admin\Model\TypeReflection;

use Hyva\Admin\Model\MethodValueBindings;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\ObjectManagerInterface;

use function array_keys as keys;
use function array_map as map;
use function array_values as values;

class TypeMethod
{
    /**
     * @var MethodValueBindings
     */
    private $methodValueBindings;

    /**
     * @var MethodsMap
     */
    private $methodsMap;

    /**
     * @var MagentoOrmReflection
     */
    private $magentoOrmReflection;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(
        MethodValueBindings $methodValueBindings,
        MethodsMap $methodsMap,
        MagentoOrmReflection $magentoOrmReflection,
        ObjectManagerInterface $objectManager
    ) {
        $this->methodValueBindings  = $methodValueBindings;
        $this->methodsMap           = $methodsMap;
        $this->objectManager        = $objectManager;
        $this->magentoOrmReflection = $magentoOrmReflection;
    }

    /**
     * Return the return value from the configured method
     *
     * @param string $type
     * @param string $method
     * @param mixed[] $bindArguments
     * @return mixed
     */
    public function invoke(string $type, string $method, array $bindArguments)
    {
        $argumentValueMap = $this->methodValueBindings->resolveAll($bindArguments);
        $parameters       = $this->methodsMap->getRealMethodParameters($type, $method);
        $arguments        = values(map(function (string $parameter) use ($type, $method, $argumentValueMap) {
            return $argumentValueMap[$parameter] ?? $this->getDefaultValue($type, $method, $parameter);
        }, keys($parameters)));
        try {
            if ($this->isResourceModelLoadMethod($type, $method) && $arguments[0] === null) {
                $arguments[0] = $this->createOrmModelInstanceForResourceMode($type);
            }
            $result = $this->objectManager->get($type)->$method(...$arguments);

            // resourceModel::load returns the resource model, need to return first argument if loaded
            if ($this->isResourceModelLoadMethod($type, $method)) {
                return $arguments[0]->getId() ? $arguments[0] : null;
            }
            if (is_object($result) && method_exists($result, 'getId')) {
                return $result->getId() ? $result : null;
            }
            if (is_array($result)) {
                return $result ?: null;
            }
            return $result;
        } catch (NoSuchEntityException $exception) {
            return null;
        }
    }

    private function getDefaultValue(string $type, string $method, string $parameter)
    {
        return $this->methodsMap->parameterHasDefaultValue($type, $method, $parameter)
            ? $this->methodsMap->getParameterDefaultValue($type, $method, $parameter)
            : null;
    }

    private function isResourceModelLoadMethod(string $type, string $method): bool
    {
        return $method === 'load' && $this->magentoOrmReflection->extendsAbstractResourceModel($type);
    }

    private function createOrmModelInstanceForResourceMode(string $type): ?AbstractModel
    {
        $modelClass = str_replace('\Model\ResourceModel\\', '\Model\\', $type);
        return class_exists($modelClass) && is_subclass_of($modelClass, AbstractModel::class)
            ? $this->objectManager->create($modelClass)
            : null;
    }

    public static function split(string $typeAndMethod): array
    {
        if (!$typeAndMethod || !preg_match('/^.+::.+$/', $typeAndMethod)) {
            $msg = sprintf('Invalid method specified: "%s"', $typeAndMethod);
            throw new \RuntimeException($msg);
        }
        return explode('::', $typeAndMethod);
    }
}
