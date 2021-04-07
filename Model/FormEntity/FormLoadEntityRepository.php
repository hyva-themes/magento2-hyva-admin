<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormEntity;

use Hyva\Admin\Model\TypeReflection\TypeMethod;
use Magento\Framework\ObjectManagerInterface;

class FormLoadEntityRepository
{
    /**
     * @var TypeMethod
     */
    private $typeMethod;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(TypeMethod $typeMethod, ObjectManagerInterface $objectManager)
    {
        $this->typeMethod    = $typeMethod;
        $this->objectManager = $objectManager;
    }

    public function fetchTypeAndMethod(string $typeAndMethod, array $bindArguments, string $valueType): FormLoadEntity
    {
        [$type, $method] = $this->typeMethod->split($typeAndMethod);
        return $this->fetch($type, $method, $bindArguments, $valueType);
    }

    private function fetch(string $type, string $method, array $bindArguments, string $valueType): FormLoadEntity
    {
        $value = $this->typeMethod->invoke($type, $method, $bindArguments);

        if ($value && (interface_exists($valueType) || class_exists($valueType)) && ! $value instanceof $valueType) {
            $msg = sprintf('Form entity type mismatch: "%s" is not a %s', $this->typeStr($value), $valueType);
            throw new \RuntimeException($msg);
        }

        return $this->objectManager->create(FormLoadEntity::class, [
            'value' => $value,
            'valueType' => $valueType
        ]);
    }

    private function typeStr($value): string
    {
        if (is_array($value)) {
            return 'array';
        }
        if (is_scalar($value)) {
            return gettype($value);
        }
        if (is_null($value)) {
            return 'NULL';
        }
        if (is_object($value)) {
            return get_class($value);
        }
        return 'unknown';
    }
}
