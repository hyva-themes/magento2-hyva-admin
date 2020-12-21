<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model\TypeReflection\Stub;

/**
 * @method StubReflectionTargetGrandchild getMethodAnnotationWithType()
 * @method string|null getMethodAnnotationWithTypeOrNull()
 * @method null|string getMethodAnnotationWithNullOrType()
 * @method null getMethodAnnotationWithNullReturnType()
 * @method void getMethodAnnotationWithVoidReturnType()
 * @method getMethodAnnotationWithNoReturnType()
 * @method string|int getMethodAnnotationWithTwoReturnTypes()
 */
class StubReflectionTargetGrandchild extends StubReflectionTargetChild
{
    public function getMethodWithInheritedReturnAnnotation()
    {
        return parent::getMethodWithInheritedReturnAnnotation();
    }

    public function getMethodWithoutInheritedReturnAnnotation()
    {
        return parent::getMethodWithoutInheritedReturnAnnotation();
    }

    /**
     * @return int
     */
    public function getMethodWithReturnAnnotation()
    {
        return 1;
    }

    /**
     * @return string[]
     */
    public function getMethodWithAnnotatedArrayReturnType(): array
    {
        return [];
    }

    public function getMethodWithOnlySignatureReturnType(): string
    {
        return '';
    }
}
