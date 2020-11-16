<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\ViewModel\HyvaGrid;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinition;
use PHPUnit\Framework\TestCase;

class ColumnDefinitionTest extends TestCase
{
    /**
     * @dataProvider columnKeyLabelProvider
     */
    public function testDerivesDefaultLabelFromColumnKey($key, $expectedLabel): void
    {
        $this->assertSame($expectedLabel, (new ColumnDefinition($key))->getLabel());
    }

    public function columnKeyLabelProvider(): array
    {
        return [
            ['foo', 'Foo'],
            ['fooBar', 'Foo Bar'],
            ['FooBar', 'Foo Bar'],
            ['FooBarCC', 'Foo Bar CC'],
            ['foo_bar', 'Foo Bar'],
            ['foo_Bar', 'Foo Bar'],
            ['bar   baz', 'Bar Baz'],
        ];
    }
}
