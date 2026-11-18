<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Integration\Model\DataType;

use Hyva\Admin\Model\DataType\ScalarAndNullDataType;
use PHPUnit\Framework\TestCase;

/**
 * @magentoAppArea adminhtml
 */
class ScalarAndNullDataTypeTest extends TestCase
{
    /**
     * @dataProvider notMatchingTypeProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('notMatchingTypeProvider')]
    public function testReturnsNullForNonMatchingTypes($nonMatchingType): void
    {
        $sut = new ScalarAndNullDataType();
        $this->assertNull($sut->toString($nonMatchingType));
        $this->assertNull($sut->toHtmlRecursive($nonMatchingType));
    }

    public static function notMatchingTypeProvider(): array
    {
        return [
            'array'    => [[]],
            'object'   => [new \stdClass()],
            'resource' => [tmpfile()],
        ];
    }

    /**
     * @dataProvider  matchingTypeProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('matchingTypeProvider')]
    public function testReturnsStringForMatchingTypes($matchingType): void
    {
        $sut = new ScalarAndNullDataType();
        $this->assertSame((string) $matchingType, $sut->toString($matchingType));
        $this->assertSame((string) $matchingType, $sut->toHtmlRecursive($matchingType));
    }

    public static function matchingTypeProvider(): array
    {
        return [
            'int'    => [1],
            'string' => ['foo'],
            'float'  => [pi()],
            'bool'   => [false],
            'null'   => [null],
        ];
    }
}
