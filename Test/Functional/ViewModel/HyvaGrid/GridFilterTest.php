<?php declare(strict_types=1);

namespace Hyva\Admin\Test\Functional\ViewModel\HyvaGrid;

use Hyva\Admin\ViewModel\HyvaGrid\ColumnDefinitionInterface;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilter;
use Hyva\Admin\ViewModel\HyvaGrid\GridFilterInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

use function array_merge as merge;

/**
 * @magentoAppArea adminhtml
 */
class GridFilterTest extends TestCase
{
    private function createFilter(array $filterArguments, array $columnArguments = []): GridFilter
    {
        $columnArgs = merge(['key' => 'test'], $columnArguments);
        $dummyCol   = ObjectManager::getInstance()->create(ColumnDefinitionInterface::class, $columnArgs);
        $filterArgs = merge(['columnDefinition' => $dummyCol], $filterArguments);
        return ObjectManager::getInstance()->create(GridFilterInterface::class, $filterArgs);
    }

    public function testIsKnownToTheObjectManager(): void
    {
        $this->assertInstanceOf(GridFilter::class, $this->createFilter([]));
    }

    public function testIsDisabledByDefault(): void
    {
        $this->assertFalse($this->createFilter([])->isEnabled());
    }

    public function isEnabledWhenSpecified(): void
    {
        $this->assertTrue($this->createFilter(['enabled' => 'true'])->isEnabled());
    }

    public function testThrowsExceptionIfNoTemplateIsSetForInputType(): void
    {
        $sut = $this->createFilter(['input' => 'nix']);

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('No template is set for the grid filter input type "nix".');

        $sut->getHtml();
    }

    public function testRendersGridInputTypeBasedTemplate(): void
    {
        $sut = $this->createFilter([
            'input'                => 'foo',
            'inputTypeTemplateMap' => ['foo' => 'Hyva_Admin::testing/filter-test.phtml'],
        ]);

        $expected = 'Filter for column test with input type foo.';

        $this->assertSame($expected, trim($sut->getHtml()));
    }

    /**
     * @dataProvider columnDefinitionInputProvider
     */
    public function testDeterminesInputTypeBasedOnColumnDefinition(string $expected, array $columnArguments): void
    {
        $this->assertSame($expected, $this->createFilter([], $columnArguments)->getInputType());
    }

    public function columnDefinitionInputProvider(): array
    {
        // expected filter type => column constructor args
        return [
            'none'     => ['text', []],
            'scalar'   => ['text', ['type' => 'scalar_null']],
            'unknown'  => ['text', ['type' => 'unknown']],
            'options'  => ['select', ['options' => [['value' => 'foo', 'label' => 'Foo']]]],
            'bool'     => ['bool', ['type' => 'bool']],
            'datetime' => ['date-range', ['type' => 'datetime']],
        ];
    }

    public function testHasTemplateForTextInputType(): void
    {
        $sut = $this->createFilter(['input' => 'text']);
        $this->assertStringContainsString('<input type="text"', $sut->getHtml());
    }

    public function testHasTemplateForSelectInputType(): void
    {
        $sut = $this->createFilter(['input' => 'select', 'options' => [['label' => 'x', 'value' => 'y']]]);
        $this->assertStringContainsString('<select', $sut->getHtml());
    }

    public function testHasTemplateForBooleanInputType(): void
    {
        $sut = $this->createFilter(['input' => 'bool']);
        $this->assertStringContainsString('<option value="0" selected>False', $sut->getHtml());
    }

    public function testHasTemplateForDateRangeInputType(): void
    {
        $sut = $this->createFilter(['input' => 'date-range']);
        $this->assertStringContainsString('From: <input type="date"', $sut->getHtml());
    }

    public function testHasTemplateForValueRangeInputType(): void
    {
        $sut = $this->createFilter(['input' => 'value-range']);
        $this->assertStringContainsString('From: <input type="text"', $sut->getHtml());
    }

    public function testUsesGridNameToQualifyFilterFieldNames(): void
    {
        $this->markTestIncomplete();
    }

    public function testUsesFilterOptionsIfSpecified(): void
    {
        $this->markTestIncomplete();
    }

    public function testUsesColumnOptionsForSelect(): void
    {
        $this->markTestIncomplete();
    }

    public function testReturnsFilterValueFromRequestOrNull(): void
    {
        $this->markTestIncomplete();
    }
}
