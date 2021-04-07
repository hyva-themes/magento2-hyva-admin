<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model\TypeReflection;

use Hyva\Admin\Model\TypeReflection\MethodsMap;
use Hyva\Admin\Model\TypeReflection\NamespaceMapper;
use Hyva\Admin\Test\Unit\Model\TypeReflection\Stub\StubMethodParameterReflection;
use Hyva\Admin\Test\Unit\Model\TypeReflection\Stub\StubReflectionTargetChild;
use Hyva\Admin\Test\Unit\Model\TypeReflection\Stub\StubReflectionTargetGrandchild;
use Hyva\Admin\Test\Unit\Model\TypeReflection\Stub\StubReflectionTargetParent;
use Magento\Framework\Reflection\FieldNamer;
use PHPUnit\Framework\TestCase;

/**
 * @method string getTestMethodAnnotation()
 * @method string|null getTestMethodAnnotationWithStringNull()
 * @method null|string getTestMethodAnnotationWithNullString()
 * @method getTestMethodAnnotationWithNoReturnType()
 * @method null getTestMethodAnnotationWithOnlyNullReturn()
 * @method void getTestMethodAnnotationWithVoidReturn()
 * @see The above method annotations are used in tests
 * @author Someone called Vinai
 */
class MethodsMapTest extends TestCase
{
    public function testIncludesPublicMethods(): void
    {
        $sut     = new MethodsMap(new FieldNamer(), new NamespaceMapper());
        $methods = $sut->getMethodsReturnTypeMap(__CLASS__);
        $this->assertArrayHasKey(__FUNCTION__, $methods);
    }

    public function testIncludesAnnotatedMethods(): void
    {
        $sut     = new MethodsMap(new FieldNamer(), new NamespaceMapper());
        $methods = $sut->getMethodsReturnTypeMap(Stub\StubReflectionTargetGrandchild::class);
        $this->assertArrayHasKey('getMethodAnnotationWithType', $methods);
    }

    public function testReturnsReturnTypeOfRealMethods(): void
    {
        $sut   = new MethodsMap(new FieldNamer(), new NamespaceMapper());
        $class = Stub\StubReflectionTargetGrandchild::class;
        $this->assertSame('string', $sut->getMethodReturnType($class, 'getMethodWithOnlySignatureReturnType'));
        $this->assertSame('int', $sut->getMethodReturnType($class, 'getMethodWithSignatureFromParent'));
        $this->assertSame('string', $sut->getMethodReturnType($class, 'getMethodWithAnnotationAndSignatureFromParent'));
    }

    public function testReturnsReturnTypeOfAnnotatedMethods(): void
    {
        $sut   = new MethodsMap(new FieldNamer(), new NamespaceMapper());
        $class = Stub\StubReflectionTargetGrandchild::class;
        $this->assertSame($class, $sut->getMethodReturnType($class, 'getMethodAnnotationWithType'));
        $this->assertSame('string', $sut->getMethodReturnType($class, 'getMethodAnnotationWithTypeOrNull'));
        $this->assertSame('string', $sut->getMethodReturnType($class, 'getMethodAnnotationWithNullOrType'));
        $this->assertSame('mixed', $sut->getMethodReturnType($class, 'getMethodAnnotationWithNoReturnType'));
        $this->assertSame('void', $sut->getMethodReturnType($class, 'getMethodAnnotationWithNullReturnType'));
        $this->assertSame('void', $sut->getMethodReturnType($class, 'getMethodAnnotationWithVoidReturnType'));
        $this->assertSame('string', $sut->getMethodReturnType($class, 'getMethodAnnotationWithTwoReturnTypes'));
        $this->assertSame('int', $sut->getMethodReturnType($class, 'getMethodWithReturnAnnotationFromParent'));
    }

    public function testIsValidForDataField(): void
    {
        $sut = new MethodsMap(new FieldNamer(), new NamespaceMapper());
        $this->assertFalse($sut->isMethodValidGetter(__CLASS__, 'nonExistentMethod'));
        $this->assertFalse($sut->isMethodValidGetter(__CLASS__, 'testIsValidForDataField'));
        $this->assertFalse($sut->isMethodValidGetter(__CLASS__, 'getMockBuilder'));
        $this->assertFalse($sut->isMethodValidGetter(__CLASS__, 'getTestMethodAnnotationWithOnlyNullReturn'));
        $this->assertFalse($sut->isMethodValidGetter(__CLASS__, 'getTestMethodAnnotationWithVoidReturn'));
        $this->assertTrue($sut->isMethodValidGetter(__CLASS__, 'getTestMethodAnnotation'));
    }

    public function testUsedDefaultReturnTypeMixed(): void
    {
        $sut   = new MethodsMap(new FieldNamer(), new NamespaceMapper());
        $class = Stub\StubReflectionTargetGrandchild::class;
        $this->assertSame('mixed', $sut->getMethodReturnType($class, 'getMethodWithoutInheritedReturnAnnotation'));
    }

    public function testUsesReturnAnnotationIfNeeded(): void
    {
        $sut   = new MethodsMap(new FieldNamer(), new NamespaceMapper());
        $class = Stub\StubReflectionTargetGrandchild::class;
        $this->assertSame('int', $sut->getMethodReturnType($class, 'getMethodWithReturnAnnotation'));
    }

    public function testUsesReturnAnnotationOfParentIfNeeded(): void
    {
        $sut   = new MethodsMap(new FieldNamer(), new NamespaceMapper());
        $class = Stub\StubReflectionTargetGrandchild::class;
        $this->assertSame('string', $sut->getMethodReturnType($class, 'getMethodWithInheritedReturnAnnotation'));
    }

    public function testReturnsAnnotatedArrayType(): void
    {
        $sut   = new MethodsMap(new FieldNamer(), new NamespaceMapper());
        $class = Stub\StubReflectionTargetGrandchild::class;
        $this->assertSame('string[]', $sut->getMethodReturnType($class, 'getMethodWithAnnotatedArrayReturnType'));
    }

    public function testReturnsParameterTypes(): void
    {
        $sut   = new MethodsMap(new FieldNamer(), new NamespaceMapper());
        $class = StubMethodParameterReflection::class;
        $this->assertNull($sut->getParameterType($class, 'noType', 'arg'));
        $this->assertSame('int', $sut->getParameterType($class, 'annotatedType', 'arg'));
        $this->assertSame('string', $sut->getParameterType($class, 'signatureType', 'arg'));
        $this->assertSame('string', $sut->getParameterType($class, 'nullableType', 'arg'));
        $this->assertSame('string', $sut->getParameterType($class, 'optionalType', 'arg'));
        $this->assertSame('string', $sut->getParameterType($class, 'nullableOptionalType', 'arg'));
        $this->assertSame('bool', $sut->getParameterType($class, 'inheritedAnnotation', 'arg'));
        $this->assertSame(StubReflectionTargetChild::class, $sut->getParameterType($class, 'relativeType', 'arg'));
        $this->assertSame(MethodsMap::class, $sut->getParameterType($class, 'importedType', 'arg'));
        $this->assertSame(StubReflectionTargetChild::class, $sut->getParameterType($class, 'annotatedRelative', 'arg'));
        $this->assertSame(MethodsMap::class, $sut->getParameterType($class, 'annotatedImportedType', 'arg'));
    }

    public function testReturnsTypeFromInterfaceIfNotOverridden(): void
    {
        $sut   = new MethodsMap(new FieldNamer(), new NamespaceMapper());
        $returnType = $sut->getMethodReturnType(StubReflectionTargetParent::class, 'getIdentities');
        $this->assertSame('string[]', $returnType);
    }
}
