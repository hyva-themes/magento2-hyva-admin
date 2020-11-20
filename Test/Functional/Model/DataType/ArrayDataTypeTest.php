<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Functional\Model\DataType;

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

        $this->assertNull($sut->typeOf(1));
        $this->assertNull($sut->typeOf('a string'));
        $this->assertNull($sut->typeOf(1.12321));
        $this->assertNull($sut->typeOf(null));
        $this->assertNull($sut->typeOf(new \stdClass()));
        $this->assertNull($sut->typeOf(tmpfile()));
    }

    public function testMatchesArray(): void
    {
        $this->assertSame(ArrayDataType::TYPE_ARRAY, $this->createArrayDataType()->typeOf([]));
        $this->assertSame(ArrayDataType::TYPE_ARRAY, $this->createArrayDataType()->typeOf([1, 2, 3]));
    }

    public function testReturnsNullIfTypeDoesNotMatch(): void
    {
        $this->assertNull($this->createArrayDataType()->toString('foo'));
        $this->assertNull($this->createArrayDataType()->toStringRecursive('foo'));
    }

    public function testArrayToStringWithoutRecursion(): void
    {
        $this->assertSame('[ ]', $this->createArrayDataType()->toString([]));
        $this->assertSame('[...(1)...]', $this->createArrayDataType()->toString(['a']));
        $this->assertSame('[...(3)...]', $this->createArrayDataType()->toString([3, 2, 1]));
    }

    public function testArrayToStringWithRecursion(): void
    {
        $sut = $this->createArrayDataType();
        $this->assertSame('[ ]', $sut->toStringRecursive([]));
        $this->assertSame('[1]', $sut->toStringRecursive([1]));
        $this->assertSame('[1, a]', $sut->toStringRecursive([1, 'a']));
        $this->assertSame('[[ ], a]', $sut->toStringRecursive([[], 'a']));

        $o = new class() {
            public function __toString(): string
            {
                return 'foo';
            }
        };
        $this->assertSame('[[ ], a, foo]', $sut->toStringRecursive([[], 'a', $o]));

        $noLimit = ArrayDataType::UNLIMITED_RECURSION;
        $this->assertSame('[[[[ ]]]]', $sut->toStringRecursive([[[[]]]], $noLimit));
        $this->assertSame('[[[[10, 9, 8]]]]', $sut->toStringRecursive([[[[10, 9, 8]]]], $noLimit));
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
        $this->assertSame($expected, $sut->toStringRecursive($value, $recursionDepth));
    }

    public function recursionDepthDataProvider(): array
    {
        return [
            [0, '[...(2)...]'],
            [1, '[[...(2)...], [...(1)...]]'],
            [2, '[[[...(2)...], [ ]], [[...(3)...]]]'],
            [3, '[[[1, [...(2)...]], [ ]], [[4, 5, [ ]]]]'],
            [4, '[[[1, [2, 3]], [ ]], [[4, 5, [ ]]]]'],
            [5, '[[[1, [2, 3]], [ ]], [[4, 5, [ ]]]]'],
        ];
    }
}
