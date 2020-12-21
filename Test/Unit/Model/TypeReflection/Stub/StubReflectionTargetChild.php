<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model\TypeReflection\Stub;

class StubReflectionTargetChild extends StubReflectionTargetParent
{

    public function getMethodWithoutInheritedReturnAnnotation()
    {
        return null;
    }
}
