<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\ViewModel\HyvaGrid;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinition;
use PHPUnit\Framework\TestCase;

use function array_values as values;

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

    public function testToArrayAndReinstantiate(): void
    {
        $original = [
            'key' => 'the_key',
            'label' => 'The label',
            'type' => 'string',
            'renderer' => 'My\Renderer\Block',
            'source' => 'My\Source\Model',
            'options' => [
                ['value' => 'aaa', 'label' => 'Aaa'],
                ['value' => 'bbb', 'label' => 'Bbb'],
            ]
        ];
        $column1 = new ColumnDefinition(...values($original));
        $column2 = new ColumnDefinition(...values($column1->toArray()));

        $this->assertSame($original, $column1->toArray());
        $this->assertSame($column1->toArray(), $column2->toArray());

    }
}
