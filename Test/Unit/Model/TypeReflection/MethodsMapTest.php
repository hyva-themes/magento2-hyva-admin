<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\Model\TypeReflection;

use Hyva\Admin\Model\TypeReflection\MethodsMap;
use Magento\Framework\Reflection\FieldNamer;
use PHPUnit\Framework\MockObject\MockBuilder;
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
        $sut = new MethodsMap(new FieldNamer());
        $methods = $sut->getMethodsMap(__CLASS__);
        $this->assertArrayHasKey(__FUNCTION__, $methods);
    }

    public function testIncludesAnnotatedMethods(): void
    {
        $sut = new MethodsMap(new FieldNamer());
        $methods = $sut->getMethodsMap(__CLASS__);
        $this->assertArrayHasKey('getTestMethodAnnotation', $methods);
    }

    public function testReturnsReturnTypeOfRealMethods(): void
    {
        $sut = new MethodsMap(new FieldNamer());
        $this->assertSame(MockBuilder::class, $sut->getMethodReturnType(__CLASS__, 'getMockBuilder'));
    }

    public function testReturnsReturnTypeOfAnnotatedMethods(): void
    {
        $sut = new MethodsMap(new FieldNamer());
        $this->assertSame('string', $sut->getMethodReturnType(__CLASS__, 'getTestMethodAnnotation'));
        $this->assertSame('string', $sut->getMethodReturnType(__CLASS__, 'getTestMethodAnnotationWithStringNull'));
        $this->assertSame('string', $sut->getMethodReturnType(__CLASS__, 'getTestMethodAnnotationWithNullString'));
        $this->assertSame('mixed', $sut->getMethodReturnType(__CLASS__, 'getTestMethodAnnotationWithNoReturnType'));
        $this->assertSame('void', $sut->getMethodReturnType(__CLASS__, 'getTestMethodAnnotationWithOnlyNullReturn'));
        $this->assertSame('void', $sut->getMethodReturnType(__CLASS__, 'getTestMethodAnnotationWithVoidReturn'));
    }

    public function testIsValidForDataField(): void
    {
        $sut = new MethodsMap(new FieldNamer());
        $this->assertFalse($sut->isMethodValidForDataField(__CLASS__, 'nonExistentMethod'));
        $this->assertFalse($sut->isMethodValidForDataField(__CLASS__, 'testIsValidForDataField'));
        $this->assertFalse($sut->isMethodValidForDataField(__CLASS__, 'getMockBuilder'));
        $this->assertFalse($sut->isMethodValidForDataField(__CLASS__, 'getTestMethodAnnotationWithOnlyNullReturn'));
        $this->assertFalse($sut->isMethodValidForDataField(__CLASS__, 'getTestMethodAnnotationWithVoidReturn'));
        $this->assertTrue($sut->isMethodValidForDataField(__CLASS__, 'getTestMethodAnnotation'));
    }
}
