<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\DataType;

use Hyva\Admin\Model\DataType\ArrayDataType;
use Magento\Framework\App\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class ArrayDataTypeTest extends TestCase
{
    private function createArrayDataType(): ArrayDataType
    {
        return ObjectManager::getInstance()->create(ArrayDataType::class);
    }

    public function testDoesNotMatchNonArrayTypes(): void
    {
        $sut = $this->createArrayDataType();

        $this->assertNull($sut->valueToTypeCode(1));
        $this->assertNull($sut->valueToTypeCode('a string'));
        $this->assertNull($sut->valueToTypeCode(1.12321));
        $this->assertNull($sut->valueToTypeCode(null));
        $this->assertNull($sut->valueToTypeCode(new \stdClass()));
        $this->assertNull($sut->valueToTypeCode(tmpfile()));
    }

    public function testMatchesArray(): void
    {
        $this->assertSame(ArrayDataType::TYPE_ARRAY, $this->createArrayDataType()->valueToTypeCode([]));
        $this->assertSame(ArrayDataType::TYPE_ARRAY, $this->createArrayDataType()->valueToTypeCode([1, 2, 3]));
    }

    public function testReturnsNullIfTypeDoesNotMatch(): void
    {
        $this->assertNull($this->createArrayDataType()->toString('foo'));
        $this->assertNull($this->createArrayDataType()->toHtmlRecursive('foo'));
    }

    public function testArrayToStringWithoutRecursion(): void
    {
        $this->assertSame('[ ]', $this->createArrayDataType()->toString([]));
        $this->assertSame('[a]', $this->createArrayDataType()->toString(['a']));
        $this->assertSame('[3, 2, 1, [...]]', $this->createArrayDataType()->toString([3, 2, 1, [4, [5]]]));
    }

    public function testArrayToStringWithRecursion(): void
    {
        $sut = $this->createArrayDataType();
        $this->assertSame('[ ]', $sut->toHtmlRecursive([]));
        $this->assertSame('[1]', $sut->toHtmlRecursive([1]));
        $this->assertSame('[1, a]', $sut->toHtmlRecursive([1, 'a']));
        $this->assertSame('[[ ], a]', $sut->toHtmlRecursive([[], 'a']));

        $o = new class() {
            public function __toString(): string
            {
                return 'foo';
            }
        };
        $this->assertSame('[[ ], a, foo]', $sut->toHtmlRecursive([[], 'a', $o]));

        $noLimit = ArrayDataType::UNLIMITED_RECURSION;
        $this->assertSame('[[[[ ]]]]', $sut->toHtmlRecursive([[[[]]]], $noLimit));
        $this->assertSame('[[[[10, 9, 8]]]]', $sut->toHtmlRecursive([[[[10, 9, 8]]]], $noLimit));
    }

    /**
     * @dataProvider recursionDepthDataProvider
     */
    public function testArraytoStringWithLimitedRecursion(int $recursionDepth, string $expected): void
    {
        $sut   = $this->createArrayDataType();
        $value = [
            [
                [
                    1,
                    [2, 3],
                ],
                [],
            ],
            [
                [4, 5, []],
            ],
        ];
        $this->assertSame($expected, $sut->toHtmlRecursive($value, $recursionDepth));
    }

    public function recursionDepthDataProvider(): array
    {
        // recursion depth => expected
        return [
            [0, '[...]'],
            [1, '[[...], [...]]'],
            [2, '[[[...], [ ]], [[...]]]'],
            [3, '[[[1, [...]], [ ]], [[4, 5, [ ]]]]'],
            [4, '[[[1, [2, 3]], [ ]], [[4, 5, [ ]]]]'],
            [5, '[[[1, [2, 3]], [ ]], [[4, 5, [ ]]]]'],
            [ArrayDataType::UNLIMITED_RECURSION, '[[[1, [2, 3]], [ ]], [[4, 5, [ ]]]]'],
        ];
    }
}
