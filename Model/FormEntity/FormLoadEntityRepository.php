<?php declare(strict_types=1);

namespace Hyva\Admin\Model\FormEntity;

use Hyva\Admin\Model\FormLoadEntity;
use Hyva\Admin\Model\TypeReflection\TypeMethod;
use Magento\Framework\ObjectManagerInterface;

class FormLoadEntityRepository
{
    private TypeMethod $typeMethod;

    private ObjectManagerInterface $objectManager;

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

        return $this->objectManager->create(FormLoadEntity::class, [
            'value' => $value,
            'valueType' => $valueType
        ]);
    }
}
