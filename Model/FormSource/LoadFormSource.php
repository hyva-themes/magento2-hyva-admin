<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormSource;

use Hyva\Admin\Model\MethodValueBindings;
use Hyva\Admin\Model\TypeReflection\MethodsMap;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\ObjectManagerInterface;

use function array_map as map;
use function array_values as values;

class LoadFormSource
{
    private MethodValueBindings $methodValueBindings;

    private MethodsMap $methodsMap;

    private ObjectManagerInterface $objectManager;

    public function __construct(
        MethodValueBindings $methodValueBindings,
        MethodsMap $methodsMap,
        ObjectManagerInterface $objectManager
    ) {
        $this->methodValueBindings = $methodValueBindings;
        $this->methodsMap          = $methodsMap;
        $this->objectManager       = $objectManager;
    }

    /**
     * Return the return value from the configured load method if the load was successful or null
     *
     * @param string $type
     * @param string $method
     * @param array[] $bindArguments
     * @return mixed
     */
    public function invoke(string $type, string $method, array $bindArguments)
    {
        $argumentValueMap    = $this->methodValueBindings->resolveAll($bindArguments);
        $parameters          = $this->methodsMap->getRealMethodParameters($type, $method);
        $parameterValueMap   = map(fn(string $parameter) => $argumentValueMap[$parameter] ?? null, $parameters);
        try {
            // todo: add magic for resource models to automatically create new model and pass as first argument.
            $entity = $this->objectManager->get($type)->$method(...values($parameterValueMap));
            if (is_object($entity) && method_exists($entity, 'getId')) {
                return $entity->getId() ? $entity : null;
            }
            if (is_array($entity)) {
                return $entity ?: null;
            }
            return $entity;
        } catch (NoSuchEntityException $exception) {
            return null;
        }
    }
}
