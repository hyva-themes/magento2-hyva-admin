<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model\TypeReflection\Stub;

use Hyva\Admin\Model\TypeReflection\MethodsMap;

class StubMethodParameterReflection extends StubMethodParameterReflectionParent
{
    public function noType($arg)
    {

    }

    public function signatureType(string $arg)
    {

    }

    /**
     * @param int $arg
     */
    public function annotatedType($arg)
    {

    }

    public function nullableType(?string $arg)
    {

    }

    public function optionalType(string $arg = null)
    {

    }

    public function nullableOptionalType(?string $arg = null)
    {

    }

    public function inheritedAnnotation($arg)
    {

    }

    public function relativeType(StubReflectionTargetChild $arg)
    {

    }

    public function importedType(MethodsMap $arg)
    {

    }

    /**
     * @param StubReflectionTargetChild $arg
     */
    public function annotatedRelative($arg)
    {

    }

    /**
     * @param MethodsMap $arg
     */
    public function annotatedImportedType($arg)
    {

    }
}
