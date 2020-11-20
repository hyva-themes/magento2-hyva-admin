<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Functional\Model\DataType;

use Hyva\Admin\Model\DataType\UnknownDataType;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class UnknownDataTypeTest extends TestCase
{
    public function testMatchesEveryType(): void
    {
        $sut = new UnknownDataType();
        $this->assertSame(UnknownDataType::TYPE_UNKNOWN, $sut->typeOf(1));
        $this->assertSame(UnknownDataType::TYPE_UNKNOWN, $sut->typeOf(null));
        $this->assertSame(UnknownDataType::TYPE_UNKNOWN, $sut->typeOf('a string'));
        $this->assertSame(UnknownDataType::TYPE_UNKNOWN, $sut->typeOf(tmpfile()));
        $this->assertSame(UnknownDataType::TYPE_UNKNOWN, $sut->typeOf([]));
        $this->assertSame(UnknownDataType::TYPE_UNKNOWN, $sut->typeOf(2.5));
        $this->assertSame(UnknownDataType::TYPE_UNKNOWN, $sut->typeOf(new \stdClass()));
    }

    public function testThrowsExceptionIfValueCanNotBeCastToString(): void
    {
        $nonStringableObject = new class() {
            public function __toString(): string
            {
                throw new \Exception('do not cast to string');
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to cast a value of unknown type "object"');
        (new UnknownDataType())->toString($nonStringableObject);
    }

    public function testCastsValueToString(): void
    {
        $stringableObject = new class() {
            public function __toString(): string
            {
                return 'ok';
            }
        };

        $this->assertSame('ok', (new UnknownDataType())->toString($stringableObject));
    }
}
