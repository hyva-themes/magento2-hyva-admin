<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Unit\ViewModel\HyvaGrid;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinition;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;

use function array_values as values;

class ColumnDefinitionTest extends TestCase
{
    /**
     * @dataProvider columnKeyLabelProvider
     */
    public function testDerivesDefaultLabelFromColumnKey($key, $expectedLabel): void
    {
        $dummyObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->assertSame($expectedLabel, (new ColumnDefinition($dummyObjectManager, $key))->getLabel());
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
        $dummyObjectManager = $this->createMock(ObjectManagerInterface::class);
        $original           = [
            'key'                  => 'the_key',
            'label'                => 'The label',
            'type'                 => 'string',
            'sortOrder'            => '5',
            'renderAsUnsecureHtml' => 'false',
            'template'             => 'My_Module::template.phtml',
            'rendererBlockName'    => 'some-block-from-layoutxml',
            'sortable'             => 'true',
            'source'               => 'My\Source\Model',
            'options'              => [
                ['value' => 'aaa', 'label' => 'Aaa'],
                ['value' => 'bbb', 'label' => 'Bbb'],
            ],
            'isVisible'            => true,
            'initiallyHidden'      => 'true'
        ];

        $column1 = new ColumnDefinition($dummyObjectManager, ...values($original));
        $column2 = new ColumnDefinition($dummyObjectManager, ...values($column1->toArray()));

        $this->assertSame($original, $column1->toArray());
        $this->assertSame($column1->toArray(), $column2->toArray());
    }

    public function testRendersSecureHtmlByDefault(): void
    {
        $dummyObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->assertFalse(
            (new ColumnDefinition($dummyObjectManager, 'test'))->getRenderAsUnsecureHtml()
        );
        $this->assertFalse(
            (new ColumnDefinition($dummyObjectManager, 'test', null, null, null, null))->getRenderAsUnsecureHtml()
        );
        $this->assertFalse(
            (new ColumnDefinition($dummyObjectManager, 'test', null, null, null, 'false'))->getRenderAsUnsecureHtml()
        );
        $this->assertFalse(
            (new ColumnDefinition($dummyObjectManager, 'test', null, null, null, 'foo'))->getRenderAsUnsecureHtml()
        );
    }

    public function testRendersSecureHtmlByDefaultIfSpecified(): void
    {
        $dummyObjectManager = $this->createMock(ObjectManagerInterface::class);
        $this->assertTrue(
            (new ColumnDefinition($dummyObjectManager, 'test', null, null, null, 'true'))->getRenderAsUnsecureHtml()
        );
    }

    public function testIsSortableByDefault(): void
    {
        $dummyDi  = $this->createMock(ObjectManagerInterface::class);
        $sortable = null;
        $sut      = new ColumnDefinition($dummyDi, 'test', null, null, null, null, null, null, $sortable);
        $this->assertTrue($sut->isSortable());
    }

    public function testIsSortableWhenSetToTrue(): void
    {
        $dummyDi  = $this->createMock(ObjectManagerInterface::class);
        $sortable = 'true';
        $sut      = new ColumnDefinition($dummyDi, 'test', null, null, null, null, null, null, $sortable);
        $this->assertTrue($sut->isSortable());
    }

    public function testIsNotSortableWhenSetToFalse(): void
    {
        $dummyDi  = $this->createMock(ObjectManagerInterface::class);
        $sortable = 'false';
        $sut      = new ColumnDefinition($dummyDi, 'test', null, null, null, null, null, null, $sortable);
        $this->assertFalse($sut->isSortable());
    }
}
