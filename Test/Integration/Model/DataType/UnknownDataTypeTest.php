<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\DataType;

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
        $this->assertSame(UnknownDataType::TYPE_UNKNOWN, $sut->valueToTypeCode(1));
        $this->assertSame(UnknownDataType::TYPE_UNKNOWN, $sut->valueToTypeCode(null));
        $this->assertSame(UnknownDataType::TYPE_UNKNOWN, $sut->valueToTypeCode('a string'));
        $this->assertSame(UnknownDataType::TYPE_UNKNOWN, $sut->valueToTypeCode(tmpfile()));
        $this->assertSame(UnknownDataType::TYPE_UNKNOWN, $sut->valueToTypeCode([]));
        $this->assertSame(UnknownDataType::TYPE_UNKNOWN, $sut->valueToTypeCode(2.5));
        $this->assertSame(UnknownDataType::TYPE_UNKNOWN, $sut->valueToTypeCode(new \stdClass()));
    }

    public function testThrowsExceptionIfValueCanNotBeCastToString(): void
    {
        $nonStringableObject = new class() {
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
